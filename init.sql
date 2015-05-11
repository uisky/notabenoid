--
-- PostgreSQL database dump
--

SET statement_timeout = 0;
SET lock_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

--
-- Name: plpgsql; Type: EXTENSION; Schema: -; Owner: 
--

CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;


--
-- Name: EXTENSION plpgsql; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION plpgsql IS 'PL/pgSQL procedural language';


SET search_path = public, pg_catalog;

--
-- Name: ac; Type: DOMAIN; Schema: public; Owner: notabenoid
--

CREATE DOMAIN ac AS character(1)
	CONSTRAINT ac_check CHECK (((VALUE)::text = ANY (ARRAY['a'::text, 'g'::text, 'm'::text, 'o'::text])));


ALTER DOMAIN ac OWNER TO notabenoid;

--
-- Name: downloaded_book(integer, inet, inet); Type: FUNCTION; Schema: public; Owner: notabenoid
--

CREATE FUNCTION downloaded_book(_book_id integer, _ip inet, _via inet) RETURNS integer
    LANGUAGE plpgsql
    AS $$
			BEGIN
				IF _via IS NULL THEN
					IF EXISTS (SELECT 1 FROM download_log WHERE book_id = _book_id AND ip = _ip AND _via IS NULL LIMIT 1) THEN RETURN 0; END IF;
				ELSE
					IF EXISTS (SELECT 1 FROM download_log WHERE book_id = _book_id AND ip = _ip AND via = _via LIMIT 1) THEN RETURN 0; END IF;
				END IF;

				INSERT INTO download_log (book_id, ip, via) VALUES (_book_id, _ip, _via);
				UPDATE books SET n_dl = n_dl + 1, n_dl_today = n_dl_today + 1 WHERE id = _book_id;
				RETURN 1;
			END;
			$$;


ALTER FUNCTION public.downloaded_book(_book_id integer, _ip inet, _via inet) OWNER TO notabenoid;

--
-- Name: downloaded_book(integer, integer, inet, inet); Type: FUNCTION; Schema: public; Owner: notabenoid
--

CREATE FUNCTION downloaded_book(_book_id integer, _chap_id integer, _ip inet, _via inet) RETURNS integer
    LANGUAGE plpgsql
    AS $$
			BEGIN
				IF _via IS NULL THEN
					IF EXISTS (SELECT 1 FROM download_log WHERE chap_id = _chap_id AND ip = _ip AND _via IS NULL LIMIT 1) THEN RETURN 0; END IF;
				ELSE
					IF EXISTS (SELECT 1 FROM download_log WHERE chap_id = _chap_id AND ip = _ip AND via = _via LIMIT 1) THEN RETURN 0; END IF;
				END IF;

				INSERT INTO download_log (chap_id, ip, via) VALUES (_chap_id, _ip, _via);
				UPDATE books SET n_dl = n_dl + 1, n_dl_today = n_dl_today + 1 WHERE id = _book_id;
				UPDATE chapters SET n_dl = n_dl + 1, n_dl_today = n_dl_today + 1 WHERE id = _chap_id;
				RETURN 1;
			END;
			$$;


ALTER FUNCTION public.downloaded_book(_book_id integer, _chap_id integer, _ip inet, _via inet) OWNER TO notabenoid;

--
-- Name: group_join(integer, integer); Type: FUNCTION; Schema: public; Owner: notabenoid
--

CREATE FUNCTION group_join(_user_id integer, _book_id integer) RETURNS void
    LANGUAGE plpgsql
    AS $_$
			BEGIN
					IF NOT EXISTS(SELECT * FROM groups WHERE user_id = $1 AND book_id = $2) THEN
						INSERT INTO groups (user_id, book_id, status) VALUES($1, $2, 1);
					ELSE
						UPDATE groups SET status = 1 WHERE user_id = $1 AND book_id = $2;
					END IF;

					RETURN;
			END;
			$_$;


ALTER FUNCTION public.group_join(_user_id integer, _book_id integer) OWNER TO notabenoid;

--
-- Name: moder_book_cat_put(integer); Type: FUNCTION; Schema: public; Owner: notabenoid
--

CREATE FUNCTION moder_book_cat_put(_book_id integer) RETURNS void
    LANGUAGE plpgsql
    AS $_$
			BEGIN
				IF NOT EXISTS(SELECT * FROM moder_book_cat WHERE book_id = $1) THEN
					INSERT INTO moder_book_cat (book_id) VALUES($1);
				ELSE
					UPDATE moder_book_cat SET cdate = now() WHERE book_id = $1;
				END IF;

				RETURN;
			END;
			$_$;


ALTER FUNCTION public.moder_book_cat_put(_book_id integer) OWNER TO notabenoid;

--
-- Name: rate_tr(integer, integer, integer); Type: FUNCTION; Schema: public; Owner: notabenoid
--

CREATE FUNCTION rate_tr(_user_id integer, _tr_id integer, _mark integer) RETURNS void
    LANGUAGE plpgsql
    AS $_$
			BEGIN
					IF NOT EXISTS(SELECT * FROM marks WHERE user_id = $1 AND tr_id = $2) THEN
						INSERT INTO marks (user_id, tr_id, mark) VALUES($1, $2, $3);
					ELSE
						UPDATE marks SET mark = $3 WHERE user_id = $1 AND tr_id = $2;
					END IF;

					RETURN;
			END;
			$_$;


ALTER FUNCTION public.rate_tr(_user_id integer, _tr_id integer, _mark integer) OWNER TO notabenoid;

--
-- Name: ready(integer, integer); Type: FUNCTION; Schema: public; Owner: notabenoid
--

CREATE FUNCTION ready(n_verses integer, d_vars integer) RETURNS double precision
    LANGUAGE plpgsql
    AS $$
			BEGIN
				IF n_verses = 0 THEN RETURN 0; ELSE RETURN d_vars::float / n_verses::float; END IF;
			END;
			$$;


ALTER FUNCTION public.ready(n_verses integer, d_vars integer) OWNER TO notabenoid;

--
-- Name: seen_orig(integer, integer, integer); Type: FUNCTION; Schema: public; Owner: notabenoid
--

CREATE FUNCTION seen_orig(_user_id integer, _orig_id integer, _n_comments integer) RETURNS void
    LANGUAGE plpgsql
    AS $$
			BEGIN
					IF EXISTS(SELECT * FROM seen WHERE user_id = _user_id AND orig_id = _orig_id) THEN UPDATE seen SET seen=now(), n_comments = _n_comments WHERE user_id = _user_id AND orig_id = _orig_id;
					ELSE INSERT INTO seen (user_id, orig_id, seen, n_comments, track) VALUES(_user_id, _orig_id, now(), _n_comments, false);
					END IF;
					RETURN;
			END;
			$$;


ALTER FUNCTION public.seen_orig(_user_id integer, _orig_id integer, _n_comments integer) OWNER TO notabenoid;

--
-- Name: seen_post(integer, integer, integer); Type: FUNCTION; Schema: public; Owner: notabenoid
--

CREATE FUNCTION seen_post(_user_id integer, _post_id integer, _n_comments integer) RETURNS void
    LANGUAGE plpgsql
    AS $$
			BEGIN
					IF EXISTS(SELECT * FROM seen WHERE user_id = _user_id AND post_id = _post_id) THEN UPDATE seen SET seen=now(), n_comments = _n_comments WHERE user_id = _user_id AND post_id = _post_id;
					ELSE INSERT INTO seen (user_id, post_id, seen, n_comments, track) VALUES(_user_id, _post_id, now(), _n_comments, false);
					END IF;
					RETURN;
			END;
			$$;


ALTER FUNCTION public.seen_post(_user_id integer, _post_id integer, _n_comments integer) OWNER TO notabenoid;

--
-- Name: track_orig(integer, integer, integer); Type: FUNCTION; Schema: public; Owner: notabenoid
--

CREATE FUNCTION track_orig(_user_id integer, _orig_id integer, _inc integer) RETURNS void
    LANGUAGE plpgsql
    AS $_$
			BEGIN
					IF EXISTS(SELECT * FROM seen WHERE user_id = $1 AND orig_id = $2) THEN UPDATE seen SET track = true, n_comments = n_comments + _inc WHERE user_id = _user_id AND orig_id = _orig_id;
					ELSE INSERT INTO seen (user_id, orig_id, seen, n_comments, track) VALUES(_user_id, _orig_id, NULL, _inc, true);
					END IF;
					RETURN;
			END;
			$_$;


ALTER FUNCTION public.track_orig(_user_id integer, _orig_id integer, _inc integer) OWNER TO notabenoid;

--
-- Name: track_post(integer, integer, integer); Type: FUNCTION; Schema: public; Owner: notabenoid
--

CREATE FUNCTION track_post(_user_id integer, _post_id integer, _inc integer) RETURNS void
    LANGUAGE plpgsql
    AS $_$
			BEGIN
					IF EXISTS(SELECT * FROM seen WHERE user_id = $1 AND post_id = $2) THEN UPDATE seen SET track = true, n_comments = n_comments + _inc WHERE user_id = _user_id AND post_id = _post_id;
					ELSE INSERT INTO seen (user_id, post_id, seen, n_comments, track) VALUES(_user_id, _post_id, NULL, _inc, true);
					END IF;
					RETURN;
			END;
			$_$;


ALTER FUNCTION public.track_post(_user_id integer, _post_id integer, _inc integer) OWNER TO notabenoid;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: ban; Type: TABLE; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE TABLE ban (
    user_id integer NOT NULL,
    until date DEFAULT '2031-08-08'::date NOT NULL
);


ALTER TABLE ban OWNER TO notabenoid;

--
-- Name: blog_posts; Type: TABLE; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE TABLE blog_posts (
    id integer NOT NULL,
    user_id integer NOT NULL,
    book_id integer,
    cdate timestamp with time zone DEFAULT now() NOT NULL,
    n_comments integer DEFAULT 0 NOT NULL,
    lastcomment timestamp with time zone DEFAULT now(),
    topics smallint NOT NULL,
    title character varying(256),
    body text NOT NULL
);


ALTER TABLE blog_posts OWNER TO notabenoid;

--
-- Name: blog_posts_id_seq; Type: SEQUENCE; Schema: public; Owner: notabenoid
--

CREATE SEQUENCE blog_posts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE blog_posts_id_seq OWNER TO notabenoid;

--
-- Name: blog_posts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: notabenoid
--

ALTER SEQUENCE blog_posts_id_seq OWNED BY blog_posts.id;


--
-- Name: book_ban_reasons; Type: TABLE; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE TABLE book_ban_reasons (
    book_id integer NOT NULL,
    cdate timestamp without time zone DEFAULT now() NOT NULL,
    title character varying(255),
    url character varying(255),
    email character varying(255),
    message text
);


ALTER TABLE book_ban_reasons OWNER TO notabenoid;

--
-- Name: book_cat_export; Type: TABLE; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE TABLE book_cat_export (
    book_id integer,
    cat_id integer
);


ALTER TABLE book_cat_export OWNER TO notabenoid;

--
-- Name: book_id_seq; Type: SEQUENCE; Schema: public; Owner: notabenoid
--

CREATE SEQUENCE book_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE book_id_seq OWNER TO notabenoid;

--
-- Name: bookmarks; Type: TABLE; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE TABLE bookmarks (
    id integer NOT NULL,
    user_id integer NOT NULL,
    book_id integer NOT NULL,
    orig_id integer,
    ord smallint,
    note character varying(255),
    cdate timestamp with time zone DEFAULT now() NOT NULL,
    watch boolean DEFAULT false NOT NULL
);


ALTER TABLE bookmarks OWNER TO notabenoid;

--
-- Name: bookmarks_id_seq; Type: SEQUENCE; Schema: public; Owner: notabenoid
--

CREATE SEQUENCE bookmarks_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE bookmarks_id_seq OWNER TO notabenoid;

--
-- Name: bookmarks_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: notabenoid
--

ALTER SEQUENCE bookmarks_id_seq OWNED BY bookmarks.id;


--
-- Name: books; Type: TABLE; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE TABLE books (
    id integer NOT NULL,
    cdate timestamp with time zone DEFAULT now() NOT NULL,
    owner_id integer NOT NULL,
    typ character(1) DEFAULT 'A'::bpchar NOT NULL,
    opts bit(8) DEFAULT (0)::bit(8) NOT NULL,
    cat_id integer,
    topics bit(32) DEFAULT (0)::bit(32) NOT NULL,
    s_lang smallint NOT NULL,
    t_lang smallint NOT NULL,
    s_title character varying(255) NOT NULL,
    t_title character varying(255) NOT NULL,
    descr text,
    img smallint[] NOT NULL,
    n_chapters integer DEFAULT 0 NOT NULL,
    n_verses integer DEFAULT 0 NOT NULL,
    n_vars integer DEFAULT 0 NOT NULL,
    d_vars integer DEFAULT 0 NOT NULL,
    n_invites smallint DEFAULT 30 NOT NULL,
    n_dl integer DEFAULT 0 NOT NULL,
    n_dl_today integer DEFAULT 0 NOT NULL,
    last_tr timestamp with time zone,
    facecontrol smallint DEFAULT 0 NOT NULL,
    ac_read ac DEFAULT 'a'::bpchar NOT NULL,
    ac_trread ac DEFAULT 'a'::bpchar NOT NULL,
    ac_gen ac DEFAULT 'a'::bpchar NOT NULL,
    ac_rate ac DEFAULT 'a'::bpchar NOT NULL,
    ac_comment ac DEFAULT 'a'::bpchar NOT NULL,
    ac_tr ac DEFAULT 'a'::bpchar NOT NULL,
    ac_blog_r ac DEFAULT 'a'::bpchar NOT NULL,
    ac_blog_c ac DEFAULT 'a'::bpchar NOT NULL,
    ac_blog_w ac DEFAULT 'a'::bpchar NOT NULL,
    ac_chap_edit ac DEFAULT 'o'::bpchar NOT NULL,
    ac_book_edit ac DEFAULT 'o'::bpchar NOT NULL,
    ac_membership ac DEFAULT 'm'::bpchar NOT NULL,
    ac_announce ac DEFAULT 'm'::bpchar NOT NULL,
    CONSTRAINT books_ac_announce_check CHECK (((ac_announce)::bpchar = ANY (ARRAY['g'::bpchar, 'm'::bpchar, 'o'::bpchar]))),
    CONSTRAINT books_ac_book_edit_check CHECK (((ac_book_edit)::bpchar = ANY (ARRAY['m'::bpchar, 'o'::bpchar]))),
    CONSTRAINT books_ac_chap_edit_check CHECK (((ac_chap_edit)::bpchar = ANY (ARRAY['m'::bpchar, 'o'::bpchar]))),
    CONSTRAINT books_ac_membership_check CHECK (((ac_membership)::bpchar = ANY (ARRAY['m'::bpchar, 'o'::bpchar])))
);


ALTER TABLE books OWNER TO notabenoid;

--
-- Name: books_id_seq; Type: SEQUENCE; Schema: public; Owner: notabenoid
--

CREATE SEQUENCE books_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE books_id_seq OWNER TO notabenoid;

--
-- Name: books_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: notabenoid
--

ALTER SEQUENCE books_id_seq OWNED BY books.id;


--
-- Name: catalog; Type: TABLE; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE TABLE catalog (
    id integer NOT NULL,
    pid integer,
    mp smallint[] NOT NULL,
    title text,
    available boolean DEFAULT true NOT NULL
);


ALTER TABLE catalog OWNER TO notabenoid;

--
-- Name: catalog_id_seq; Type: SEQUENCE; Schema: public; Owner: notabenoid
--

CREATE SEQUENCE catalog_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE catalog_id_seq OWNER TO notabenoid;

--
-- Name: catalog_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: notabenoid
--

ALTER SEQUENCE catalog_id_seq OWNED BY catalog.id;


--
-- Name: chapters; Type: TABLE; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE TABLE chapters (
    id integer NOT NULL,
    book_id integer NOT NULL,
    cdate timestamp with time zone DEFAULT now() NOT NULL,
    last_tr timestamp with time zone,
    n_verses integer DEFAULT 0 NOT NULL,
    n_vars integer DEFAULT 0 NOT NULL,
    d_vars integer DEFAULT 0 NOT NULL,
    n_dl integer DEFAULT 0 NOT NULL,
    n_dl_today integer DEFAULT 0 NOT NULL,
    ord integer NOT NULL,
    status smallint NOT NULL,
    title character varying(300) NOT NULL,
    ac_read ac,
    ac_trread ac,
    ac_gen ac,
    ac_rate ac,
    ac_comment ac,
    ac_tr ac
);


ALTER TABLE chapters OWNER TO notabenoid;

--
-- Name: chapters_id_seq; Type: SEQUENCE; Schema: public; Owner: notabenoid
--

CREATE SEQUENCE chapters_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE chapters_id_seq OWNER TO notabenoid;

--
-- Name: chapters_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: notabenoid
--

ALTER SEQUENCE chapters_id_seq OWNED BY chapters.id;


--
-- Name: comments; Type: TABLE; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE TABLE comments (
    id integer NOT NULL,
    post_id integer,
    orig_id integer,
    pid integer,
    mp smallint[] NOT NULL,
    cdate timestamp with time zone DEFAULT now() NOT NULL,
    ip inet,
    user_id integer,
    body text NOT NULL,
    rating smallint DEFAULT 0 NOT NULL,
    n_votes smallint DEFAULT 0 NOT NULL
);


ALTER TABLE comments OWNER TO notabenoid;

--
-- Name: comments_id_seq; Type: SEQUENCE; Schema: public; Owner: notabenoid
--

CREATE SEQUENCE comments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE comments_id_seq OWNER TO notabenoid;

--
-- Name: comments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: notabenoid
--

ALTER SEQUENCE comments_id_seq OWNED BY comments.id;


--
-- Name: comments_rating; Type: TABLE; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE TABLE comments_rating (
    cdate timestamp with time zone DEFAULT now() NOT NULL,
    comment_id integer NOT NULL,
    user_id integer NOT NULL,
    mark smallint DEFAULT 0 NOT NULL
);


ALTER TABLE comments_rating OWNER TO notabenoid;

--
-- Name: dict; Type: TABLE; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE TABLE dict (
    id integer NOT NULL,
    book_id integer NOT NULL,
    cdate timestamp with time zone DEFAULT now() NOT NULL,
    user_id integer NOT NULL,
    term character varying(255) NOT NULL,
    descr character varying(255) NOT NULL
);


ALTER TABLE dict OWNER TO notabenoid;

--
-- Name: dict_id_seq; Type: SEQUENCE; Schema: public; Owner: notabenoid
--

CREATE SEQUENCE dict_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE dict_id_seq OWNER TO notabenoid;

--
-- Name: dict_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: notabenoid
--

ALTER SEQUENCE dict_id_seq OWNED BY dict.id;


--
-- Name: dima360; Type: TABLE; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE TABLE dima360 (
    id integer,
    login text
);


ALTER TABLE dima360 OWNER TO notabenoid;

--
-- Name: download_log; Type: TABLE; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE TABLE download_log (
    chap_id integer NOT NULL,
    ip inet NOT NULL,
    via inet
);


ALTER TABLE download_log OWNER TO notabenoid;

--
-- Name: group_queue; Type: TABLE; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE TABLE group_queue (
    book_id integer NOT NULL,
    user_id integer NOT NULL,
    cdate timestamp with time zone DEFAULT now() NOT NULL,
    message text
);


ALTER TABLE group_queue OWNER TO notabenoid;

--
-- Name: groups; Type: TABLE; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE TABLE groups (
    book_id integer NOT NULL,
    user_id integer NOT NULL,
    status smallint DEFAULT 0 NOT NULL,
    since timestamp with time zone DEFAULT now() NOT NULL,
    last_tr timestamp with time zone,
    n_trs integer DEFAULT 0 NOT NULL,
    rating integer DEFAULT 0 NOT NULL
);


ALTER TABLE groups OWNER TO notabenoid;

--
-- Name: invites; Type: TABLE; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE TABLE invites (
    cdate timestamp with time zone DEFAULT now() NOT NULL,
    from_uid integer NOT NULL,
    to_uid integer NOT NULL,
    book_id integer NOT NULL
);


ALTER TABLE invites OWNER TO notabenoid;

--
-- Name: karma_rates; Type: TABLE; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE TABLE karma_rates (
    dat timestamp with time zone DEFAULT now() NOT NULL,
    from_uid integer NOT NULL,
    to_uid integer NOT NULL,
    mark smallint NOT NULL,
    note character varying(255) DEFAULT ''::character varying NOT NULL,
    CONSTRAINT karma_rates_mark_check CHECK ((abs(mark) = 1))
);


ALTER TABLE karma_rates OWNER TO notabenoid;

--
-- Name: languages; Type: TABLE; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE TABLE languages (
    id smallint NOT NULL,
    typ smallint NOT NULL,
    title character varying(100),
    title_r character varying(100)
);


ALTER TABLE languages OWNER TO notabenoid;

--
-- Name: languages_id_seq; Type: SEQUENCE; Schema: public; Owner: notabenoid
--

CREATE SEQUENCE languages_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE languages_id_seq OWNER TO notabenoid;

--
-- Name: languages_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: notabenoid
--

ALTER SEQUENCE languages_id_seq OWNED BY languages.id;


--
-- Name: mail_id_seq; Type: SEQUENCE; Schema: public; Owner: notabenoid
--

CREATE SEQUENCE mail_id_seq
    START WITH 533179
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE mail_id_seq OWNER TO notabenoid;

--
-- Name: mail; Type: TABLE; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE TABLE mail (
    id integer DEFAULT nextval('mail_id_seq'::regclass) NOT NULL,
    user_id integer NOT NULL,
    buddy_id integer,
    folder smallint NOT NULL,
    cdate timestamp with time zone DEFAULT now() NOT NULL,
    subj character varying(255) NOT NULL,
    body text NOT NULL,
    seen boolean
);


ALTER TABLE mail OWNER TO notabenoid;

--
-- Name: marks; Type: TABLE; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE TABLE marks (
    user_id integer NOT NULL,
    tr_id integer NOT NULL,
    mark smallint NOT NULL,
    cdate timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE marks OWNER TO notabenoid;

--
-- Name: moder_book_cat; Type: TABLE; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE TABLE moder_book_cat (
    book_id integer NOT NULL,
    cdate timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE moder_book_cat OWNER TO notabenoid;

--
-- Name: moving; Type: TABLE; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE TABLE moving (
    ip inet NOT NULL,
    cdate timestamp with time zone DEFAULT now() NOT NULL,
    x smallint NOT NULL,
    y smallint NOT NULL,
    color smallint[] NOT NULL,
    t character varying(120) NOT NULL
);


ALTER TABLE moving OWNER TO notabenoid;

--
-- Name: notices; Type: TABLE; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE TABLE notices (
    id integer NOT NULL,
    user_id integer NOT NULL,
    cdate timestamp with time zone DEFAULT now() NOT NULL,
    seen boolean DEFAULT false NOT NULL,
    typ smallint,
    msg text DEFAULT ''::text NOT NULL
);


ALTER TABLE notices OWNER TO notabenoid;

--
-- Name: notices_id_seq; Type: SEQUENCE; Schema: public; Owner: notabenoid
--

CREATE SEQUENCE notices_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE notices_id_seq OWNER TO notabenoid;

--
-- Name: notices_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: notabenoid
--

ALTER SEQUENCE notices_id_seq OWNED BY notices.id;


--
-- Name: orig; Type: TABLE; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE TABLE orig (
    id integer NOT NULL,
    chap_id integer NOT NULL,
    ord integer,
    t1 time(3) without time zone,
    t2 time(3) without time zone,
    body text DEFAULT ''::text NOT NULL,
    n_comments smallint DEFAULT 0 NOT NULL,
    n_trs smallint DEFAULT 0 NOT NULL
);


ALTER TABLE orig OWNER TO notabenoid;

--
-- Name: orig_id_seq; Type: SEQUENCE; Schema: public; Owner: notabenoid
--

CREATE SEQUENCE orig_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE orig_id_seq OWNER TO notabenoid;

--
-- Name: orig_id_seq1; Type: SEQUENCE; Schema: public; Owner: notabenoid
--

CREATE SEQUENCE orig_id_seq1
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE orig_id_seq1 OWNER TO notabenoid;

--
-- Name: orig_id_seq1; Type: SEQUENCE OWNED BY; Schema: public; Owner: notabenoid
--

ALTER SEQUENCE orig_id_seq1 OWNED BY orig.id;


--
-- Name: orig_old_id; Type: TABLE; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE TABLE orig_old_id (
    id integer NOT NULL,
    chap_id integer NOT NULL,
    old_id integer NOT NULL
);


ALTER TABLE orig_old_id OWNER TO notabenoid;

--
-- Name: poll_answers; Type: TABLE; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE TABLE poll_answers (
    poll_id smallint NOT NULL,
    q_id smallint NOT NULL,
    user_id integer,
    cdate timestamp without time zone DEFAULT now() NOT NULL,
    ip inet NOT NULL,
    answer text DEFAULT ''::text NOT NULL
);


ALTER TABLE poll_answers OWNER TO notabenoid;

--
-- Name: poll_tmp; Type: TABLE; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE TABLE poll_tmp (
    poll_id smallint NOT NULL,
    q_id smallint NOT NULL,
    user_id integer,
    cdate timestamp without time zone NOT NULL,
    ip inet NOT NULL,
    answer text NOT NULL
);


ALTER TABLE poll_tmp OWNER TO notabenoid;

--
-- Name: recalc_log; Type: TABLE; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE TABLE recalc_log (
    book_id integer NOT NULL,
    user_id integer NOT NULL,
    dat timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE recalc_log OWNER TO notabenoid;

--
-- Name: reg_invites; Type: TABLE; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE TABLE reg_invites (
    id integer NOT NULL,
    from_id integer NOT NULL,
    to_id integer,
    to_email character varying(256),
    cdate timestamp without time zone DEFAULT now() NOT NULL,
    message text,
    code character varying(80)
);


ALTER TABLE reg_invites OWNER TO notabenoid;

--
-- Name: reg_invites_id_seq; Type: SEQUENCE; Schema: public; Owner: notabenoid
--

CREATE SEQUENCE reg_invites_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE reg_invites_id_seq OWNER TO notabenoid;

--
-- Name: reg_invites_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: notabenoid
--

ALTER SEQUENCE reg_invites_id_seq OWNED BY reg_invites.id;


--
-- Name: remind_tokens; Type: TABLE; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE TABLE remind_tokens (
    user_id integer NOT NULL,
    cdate timestamp without time zone DEFAULT now() NOT NULL,
    code character varying(80),
    attempts smallint DEFAULT 10 NOT NULL
);


ALTER TABLE remind_tokens OWNER TO notabenoid;

--
-- Name: search_history; Type: TABLE; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE TABLE search_history (
    cdate timestamp with time zone DEFAULT now() NOT NULL,
    ip inet NOT NULL,
    request character varying(255) NOT NULL
);


ALTER TABLE search_history OWNER TO notabenoid;

--
-- Name: seen; Type: TABLE; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE TABLE seen (
    user_id integer NOT NULL,
    post_id integer,
    orig_id integer,
    seen timestamp with time zone DEFAULT now(),
    n_comments integer DEFAULT 0 NOT NULL,
    track boolean DEFAULT false NOT NULL
);


ALTER TABLE seen OWNER TO notabenoid;

--
-- Name: tmp_dllog; Type: TABLE; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE TABLE tmp_dllog (
    book_id integer NOT NULL,
    chap_id integer NOT NULL,
    dat integer NOT NULL,
    ip integer NOT NULL
);


ALTER TABLE tmp_dllog OWNER TO notabenoid;

--
-- Name: translate; Type: TABLE; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE TABLE translate (
    id integer NOT NULL,
    book_id integer NOT NULL,
    chap_id integer NOT NULL,
    orig_id integer NOT NULL,
    user_id integer,
    cdate timestamp with time zone DEFAULT now() NOT NULL,
    rating smallint DEFAULT 0 NOT NULL,
    n_votes smallint DEFAULT 0 NOT NULL,
    body text NOT NULL
);


ALTER TABLE translate OWNER TO notabenoid;

--
-- Name: translate_id_seq; Type: SEQUENCE; Schema: public; Owner: notabenoid
--

CREATE SEQUENCE translate_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE translate_id_seq OWNER TO notabenoid;

--
-- Name: translate_id_seq1; Type: SEQUENCE; Schema: public; Owner: notabenoid
--

CREATE SEQUENCE translate_id_seq1
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE translate_id_seq1 OWNER TO notabenoid;

--
-- Name: translate_id_seq1; Type: SEQUENCE OWNED BY; Schema: public; Owner: notabenoid
--

ALTER SEQUENCE translate_id_seq1 OWNED BY translate.id;


--
-- Name: user_tr_stat; Type: TABLE; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE TABLE user_tr_stat (
    user_id integer NOT NULL,
    book_id integer NOT NULL,
    n_trs integer NOT NULL
);


ALTER TABLE user_tr_stat OWNER TO notabenoid;

--
-- Name: userinfo; Type: TABLE; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE TABLE userinfo (
    user_id integer NOT NULL,
    prop_id smallint NOT NULL,
    value text NOT NULL
);


ALTER TABLE userinfo OWNER TO notabenoid;

--
-- Name: users; Type: TABLE; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE TABLE users (
    id integer NOT NULL,
    cdate timestamp with time zone DEFAULT now() NOT NULL,
    lastseen timestamp with time zone DEFAULT now() NOT NULL,
    can bit(16) DEFAULT B'0000000011110011'::"bit" NOT NULL,
    login character varying(16) NOT NULL,
    pass character varying(32) NOT NULL,
    email character varying(255) NOT NULL,
    sex character(1) DEFAULT 'x'::bpchar NOT NULL,
    lang smallint NOT NULL,
    upic smallint[],
    ini bit(16) DEFAULT B'0000011100011111'::"bit" NOT NULL,
    rate_t integer DEFAULT 0 NOT NULL,
    rate_c integer DEFAULT 0 NOT NULL,
    rate_u smallint DEFAULT 0 NOT NULL,
    n_trs integer DEFAULT 0 NOT NULL,
    n_comments integer DEFAULT 0 NOT NULL,
    n_karma integer DEFAULT 0 NOT NULL,
    invited_by integer,
    n_invites smallint DEFAULT 0 NOT NULL,
    CONSTRAINT users_login_check CHECK (((login)::text <> ''::text)),
    CONSTRAINT users_pass_check CHECK (((pass)::text <> ''::text)),
    CONSTRAINT users_sex_check CHECK ((sex = ANY (ARRAY['x'::bpchar, 'm'::bpchar, 'f'::bpchar, '-'::bpchar])))
);


ALTER TABLE users OWNER TO notabenoid;

--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: notabenoid
--

CREATE SEQUENCE users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE users_id_seq OWNER TO notabenoid;

--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: notabenoid
--

ALTER SEQUENCE users_id_seq OWNED BY users.id;


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY blog_posts ALTER COLUMN id SET DEFAULT nextval('blog_posts_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY bookmarks ALTER COLUMN id SET DEFAULT nextval('bookmarks_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY books ALTER COLUMN id SET DEFAULT nextval('books_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY catalog ALTER COLUMN id SET DEFAULT nextval('catalog_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY chapters ALTER COLUMN id SET DEFAULT nextval('chapters_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY comments ALTER COLUMN id SET DEFAULT nextval('comments_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY dict ALTER COLUMN id SET DEFAULT nextval('dict_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY languages ALTER COLUMN id SET DEFAULT nextval('languages_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY notices ALTER COLUMN id SET DEFAULT nextval('notices_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY orig ALTER COLUMN id SET DEFAULT nextval('orig_id_seq1'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY reg_invites ALTER COLUMN id SET DEFAULT nextval('reg_invites_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY translate ALTER COLUMN id SET DEFAULT nextval('translate_id_seq1'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY users ALTER COLUMN id SET DEFAULT nextval('users_id_seq'::regclass);


--
-- Data for Name: ban; Type: TABLE DATA; Schema: public; Owner: notabenoid
--

COPY ban (user_id, until) FROM stdin;
\.


--
-- Data for Name: blog_posts; Type: TABLE DATA; Schema: public; Owner: notabenoid
--

COPY blog_posts (id, user_id, book_id, cdate, n_comments, lastcomment, topics, title, body) FROM stdin;
\.


--
-- Name: blog_posts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: notabenoid
--

SELECT pg_catalog.setval('blog_posts_id_seq', 1, false);


--
-- Data for Name: book_ban_reasons; Type: TABLE DATA; Schema: public; Owner: notabenoid
--

COPY book_ban_reasons (book_id, cdate, title, url, email, message) FROM stdin;
\.


--
-- Data for Name: book_cat_export; Type: TABLE DATA; Schema: public; Owner: notabenoid
--

COPY book_cat_export (book_id, cat_id) FROM stdin;
\.


--
-- Name: book_id_seq; Type: SEQUENCE SET; Schema: public; Owner: notabenoid
--

SELECT pg_catalog.setval('book_id_seq', 1, false);


--
-- Name: bookmarks_id_seq; Type: SEQUENCE SET; Schema: public; Owner: notabenoid
--

SELECT pg_catalog.setval('bookmarks_id_seq', 1, false);


--
-- Name: books_id_seq; Type: SEQUENCE SET; Schema: public; Owner: notabenoid
--

SELECT pg_catalog.setval('books_id_seq', 1, false);


--
-- Data for Name: catalog; Type: TABLE DATA; Schema: public; Owner: notabenoid
--

COPY catalog (id, pid, mp, title, available) FROM stdin;
\.


--
-- Name: catalog_id_seq; Type: SEQUENCE SET; Schema: public; Owner: notabenoid
--

SELECT pg_catalog.setval('catalog_id_seq', 1, false);


--
-- Name: chapters_id_seq; Type: SEQUENCE SET; Schema: public; Owner: notabenoid
--

SELECT pg_catalog.setval('chapters_id_seq', 1, false);


--
-- Data for Name: comments; Type: TABLE DATA; Schema: public; Owner: notabenoid
--

COPY comments (id, post_id, orig_id, pid, mp, cdate, ip, user_id, body, rating, n_votes) FROM stdin;
\.


--
-- Name: comments_id_seq; Type: SEQUENCE SET; Schema: public; Owner: notabenoid
--

SELECT pg_catalog.setval('comments_id_seq', 1, false);


--
-- Data for Name: comments_rating; Type: TABLE DATA; Schema: public; Owner: notabenoid
--

COPY comments_rating (cdate, comment_id, user_id, mark) FROM stdin;
\.


--
-- Data for Name: dict; Type: TABLE DATA; Schema: public; Owner: notabenoid
--

COPY dict (id, book_id, cdate, user_id, term, descr) FROM stdin;
\.


--
-- Name: dict_id_seq; Type: SEQUENCE SET; Schema: public; Owner: notabenoid
--

SELECT pg_catalog.setval('dict_id_seq', 1, false);


--
-- Data for Name: dima360; Type: TABLE DATA; Schema: public; Owner: notabenoid
--

COPY dima360 (id, login) FROM stdin;
\.


--
-- Data for Name: download_log; Type: TABLE DATA; Schema: public; Owner: notabenoid
--

COPY download_log (chap_id, ip, via) FROM stdin;
\.


--
-- Data for Name: group_queue; Type: TABLE DATA; Schema: public; Owner: notabenoid
--

COPY group_queue (book_id, user_id, cdate, message) FROM stdin;
\.


--
-- Data for Name: invites; Type: TABLE DATA; Schema: public; Owner: notabenoid
--

COPY invites (cdate, from_uid, to_uid, book_id) FROM stdin;
\.


--
-- Data for Name: karma_rates; Type: TABLE DATA; Schema: public; Owner: notabenoid
--

COPY karma_rates (dat, from_uid, to_uid, mark, note) FROM stdin;
\.


--
-- Data for Name: languages; Type: TABLE DATA; Schema: public; Owner: notabenoid
--

COPY languages (id, typ, title, title_r) FROM stdin;
1	10	русский	русского
2	10	английский	английского
3	10	немецкий	немецкого
4	10	французский	французского
5	10	итальянский	итальянского
6	10	испанский	испанского
7	40	китайский	китайского
8	40	японский	японского
9	20	украинский	украинского
10	20	белорусский	белорусский
11	20	молдавский	молдавского
12	20	татарский	татарского
13	30	чешский	чешского
14	200	эсперанто	эсперанто
15	40	иврит	иврита
16	30	шведский	шведского
17	30	финский	финского
18	30	норвежский	норвежского
19	30	датский	датского
20	20	абхазский	абхазского
21	20	аварский	аварского
22	20	азербайджанский	азербаджанского
23	50	аймара	аймара
24	60	акан	акан
25	30	албанский	албанского
26	60	амхарский	амхарского
27	40	арабский	арабского
28	30	арагонский	арагонского
29	30	арберешский	арберешского
30	20	армянский	армянского
31	40	ассамский	ассамского
32	30	астурийский	астурийского
33	60	афарский	афарского
34	60	африкаанс	африкаанс
35	50	ацтекский	ацтекского
36	30	баварский	баварского
37	60	бамбарийский	бамбарийского
39	30	баскский	баскского
40	20	башкирский	башкирского
41	40	бенгальский	бенгальского
42	40	бирманский	бирманского
43	70	бислама	бислама
44	40	бихари	бихари
45	30	болгарский	болгарского
47	30	бретонский	бретонского
48	30	валенсийский	валенсийского
49	30	валлийский	валлийского
50	30	валлонский	валлонского
51	30	венгерский	венгерского
52	60	венда	венда
53	30	венетский	венетского
54	30	верхнелужицкий	верхнелужицкого
55	60	волоф	волоф
57	40	вьетнамский	вьетнамского
58	70	гавайский	гавайского
59	50	гаитянский	гаитянского
60	30	галисийский	галисийского
61	60	гереро	гереро
62	30	голландский	голландского
63	30	греческий	греческого
64	20	грузинский	грузинского
65	50	гуарани	гуарани
66	40	гуджарати	гуджарати
67	30	шотландский	шотландского
68	40	дзонг-кэ	дзонг-кэ
69	200	древнегреческий	древнегреческого
70	30	западно-фламандский	западно-фламандского
71	60	зулу	зулу
72	60	игбо	игбо
73	200	идиш	идиш
74	40	илоко	илоко
75	40	индонезийский	индонезийского
76	200	интерлингва	интерлингва
77	50	инуктитут	инуктитут
78	50	инупиак	инупиак
79	30	ирландский	ирландского
80	30	исландский	исландского
81	60	йоруба	йоруба
82	60	кабильский	кабильского
83	20	казахский	казахского
84	40	каннада	каннада
85	40	кантонский юэ	кантонский юэ
86	60	канури	канури
87	30	каталанский	каталанского
88	40	кашмири	кашмири
90	50	кечуа	кечуа
91	60	кикуйю	кикуйю
92	60	киньяруанда	киньяруанда
93	20	киргизский	киргизского
94	200	клингонский	клингонского
95	20	коми	коми
96	60	конго	конго
97	40	конкани	конкани
98	40	корейский	корейского
100	30	корсиканский	корсиканского
101	200	котава	котава
102	50	кри	кри
105	40	курдский	курдского
107	40	кхмерский	кхмерского
109	40	лаосский	лаосского
110	200	латынь	латыни
111	20	латышский	латышского
112	20	лезгинский	лезгинского
113	30	лимбургский	лимбургского
114	60	лингала	лингала
115	20	литовский	литовского
118	60	луба	луба
119	60	луганда	луганда
120	30	люксембургский	люксембургского
121	30	македонский	македонского
122	60	малагасийский	малагасийского
123	40	малайский	малайского
125	40	мальдивский	мальдивского
126	30	мальтийский	мальтийского
127	70	маори	маори
130	70	маршалльский	маршалльского
133	40	монгольский	монгольского
135	50	навахо	навахо
139	30	неаполитанский	неаполитанского
140	60	непальский	непальского
147	20	осетинский	осетинского
149	40	пенджабский	пенджабского
151	40	персидский	персидского
152	30	польский	польского
153	30	португальский	португальского
158	30	румынский	румынского
163	40	санскрит	санскрита
164	30	сардинский	сардинского
166	30	сербохорватский	сербохорватского
170	30	словацкий	словацкого
171	30	словенский	словенского
172	60	сомали	сомали
174	200	старотурецкий	старотурецкого
175	60	суахили	суахили
176	40	сунданский	сунданского
177	40	тагальский	тагальского
178	20	таджикский	таджикского
179	70	таитянский	таитянского
180	40	тайваньский	тайваньского
181	40	тайский	тайского
182	40	тамильский	тамильского
184	40	телугу	телугу
185	40	тибетский	тибетского
186	60	тигринья	тигринья
187	70	ток-писин	ток-писин
188	200	токи пона	токи пона
189	70	тонганский	тонганского
191	60	тсвана	тсвана
192	60	тсонга	тсонга
193	40	турецкий	турецкого
194	20	туркменский	туркменского
196	20	удмуртский	удмуртского
197	20	узбекский	узбекского
198	40	уйгурский	уйгурского
201	70	фиджийский	фиджийского
203	30	фризский	фризского
204	60	фулах	фулах
206	60	хауса	хауса
207	40	хинди	хинди
208	70	хири-моту	хири-моту
209	40	мяо (хмонг)	мяо (хмонг)
213	20	чеченский	чеченского
217	20	чувашский	чувашского
218	30	швейцарский немецкий	швейцарского немецкого
219	60	шона	шона
221	60	эве	эве
223	20	эскимосский	эскимосского
224	20	эстонский	эстонского
225	50	юкатекский	юкатекского
227	40	яванский	яванского
229	40	филиппинский	филиппинского
230	40	пакистанский	пакистанского
231	40	южно-корейский	южно-корейского
232	20	марийский	марийского
233	20	лакский	лакского
234	30	боснийский	боснийского
235	20	калмыцкий	калмыцкого
\.


--
-- Name: languages_id_seq; Type: SEQUENCE SET; Schema: public; Owner: notabenoid
--

SELECT pg_catalog.setval('languages_id_seq', 235, true);


--
-- Name: mail_id_seq; Type: SEQUENCE SET; Schema: public; Owner: notabenoid
--

SELECT pg_catalog.setval('mail_id_seq', 1, false);


--
-- Data for Name: marks; Type: TABLE DATA; Schema: public; Owner: notabenoid
--

COPY marks (user_id, tr_id, mark, cdate) FROM stdin;
\.


--
-- Data for Name: moder_book_cat; Type: TABLE DATA; Schema: public; Owner: notabenoid
--

COPY moder_book_cat (book_id, cdate) FROM stdin;
\.


--
-- Data for Name: moving; Type: TABLE DATA; Schema: public; Owner: notabenoid
--

COPY moving (ip, cdate, x, y, color, t) FROM stdin;
\.


--
-- Data for Name: notices; Type: TABLE DATA; Schema: public; Owner: notabenoid
--

COPY notices (id, user_id, cdate, seen, typ, msg) FROM stdin;
\.


--
-- Name: notices_id_seq; Type: SEQUENCE SET; Schema: public; Owner: notabenoid
--

SELECT pg_catalog.setval('notices_id_seq', 1, false);


--
-- Name: orig_id_seq; Type: SEQUENCE SET; Schema: public; Owner: notabenoid
--

SELECT pg_catalog.setval('orig_id_seq', 1, false);


--
-- Name: orig_id_seq1; Type: SEQUENCE SET; Schema: public; Owner: notabenoid
--

SELECT pg_catalog.setval('orig_id_seq1', 1, false);


--
-- Data for Name: orig_old_id; Type: TABLE DATA; Schema: public; Owner: notabenoid
--

COPY orig_old_id (id, chap_id, old_id) FROM stdin;
\.


--
-- Data for Name: poll_answers; Type: TABLE DATA; Schema: public; Owner: notabenoid
--

COPY poll_answers (poll_id, q_id, user_id, cdate, ip, answer) FROM stdin;
\.


--
-- Data for Name: poll_tmp; Type: TABLE DATA; Schema: public; Owner: notabenoid
--

COPY poll_tmp (poll_id, q_id, user_id, cdate, ip, answer) FROM stdin;
\.


--
-- Data for Name: recalc_log; Type: TABLE DATA; Schema: public; Owner: notabenoid
--

COPY recalc_log (book_id, user_id, dat) FROM stdin;
\.




--
-- Name: reg_invites_id_seq; Type: SEQUENCE SET; Schema: public; Owner: notabenoid
--

SELECT pg_catalog.setval('reg_invites_id_seq', 1, false);


--
-- Data for Name: seen; Type: TABLE DATA; Schema: public; Owner: notabenoid
--

COPY seen (user_id, post_id, orig_id, seen, n_comments, track) FROM stdin;
\.


--
-- Data for Name: tmp_dllog; Type: TABLE DATA; Schema: public; Owner: notabenoid
--

COPY tmp_dllog (book_id, chap_id, dat, ip) FROM stdin;
\.


--
-- Name: translate_id_seq; Type: SEQUENCE SET; Schema: public; Owner: notabenoid
--

SELECT pg_catalog.setval('translate_id_seq', 1, false);


--
-- Name: translate_id_seq1; Type: SEQUENCE SET; Schema: public; Owner: notabenoid
--

SELECT pg_catalog.setval('translate_id_seq1', 1, true);


--
-- Data for Name: user_tr_stat; Type: TABLE DATA; Schema: public; Owner: notabenoid
--

COPY user_tr_stat (user_id, book_id, n_trs) FROM stdin;
\.


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: notabenoid
--

SELECT pg_catalog.setval('users_id_seq', 1, false);


--
-- Name: ban_pkey; Type: CONSTRAINT; Schema: public; Owner: notabenoid; Tablespace: 
--

ALTER TABLE ONLY ban
    ADD CONSTRAINT ban_pkey PRIMARY KEY (user_id);


--
-- Name: blog_posts_pkey; Type: CONSTRAINT; Schema: public; Owner: notabenoid; Tablespace: 
--

ALTER TABLE ONLY blog_posts
    ADD CONSTRAINT blog_posts_pkey PRIMARY KEY (id);


--
-- Name: book_ban_reasons_pkey; Type: CONSTRAINT; Schema: public; Owner: notabenoid; Tablespace: 
--

ALTER TABLE ONLY book_ban_reasons
    ADD CONSTRAINT book_ban_reasons_pkey PRIMARY KEY (book_id);


--
-- Name: bookmarks_pkey; Type: CONSTRAINT; Schema: public; Owner: notabenoid; Tablespace: 
--

ALTER TABLE ONLY bookmarks
    ADD CONSTRAINT bookmarks_pkey PRIMARY KEY (id);


--
-- Name: books_pkey; Type: CONSTRAINT; Schema: public; Owner: notabenoid; Tablespace: 
--

ALTER TABLE ONLY books
    ADD CONSTRAINT books_pkey PRIMARY KEY (id);


--
-- Name: catalog_pkey; Type: CONSTRAINT; Schema: public; Owner: notabenoid; Tablespace: 
--

ALTER TABLE ONLY catalog
    ADD CONSTRAINT catalog_pkey PRIMARY KEY (id);


--
-- Name: chapters_pkey; Type: CONSTRAINT; Schema: public; Owner: notabenoid; Tablespace: 
--

ALTER TABLE ONLY chapters
    ADD CONSTRAINT chapters_pkey PRIMARY KEY (id);


--
-- Name: comments_pkey; Type: CONSTRAINT; Schema: public; Owner: notabenoid; Tablespace: 
--

ALTER TABLE ONLY comments
    ADD CONSTRAINT comments_pkey PRIMARY KEY (id);


--
-- Name: dict_pkey; Type: CONSTRAINT; Schema: public; Owner: notabenoid; Tablespace: 
--

ALTER TABLE ONLY dict
    ADD CONSTRAINT dict_pkey PRIMARY KEY (id);


--
-- Name: languages_pkey; Type: CONSTRAINT; Schema: public; Owner: notabenoid; Tablespace: 
--

ALTER TABLE ONLY languages
    ADD CONSTRAINT languages_pkey PRIMARY KEY (id);


--
-- Name: mail_pkey; Type: CONSTRAINT; Schema: public; Owner: notabenoid; Tablespace: 
--

ALTER TABLE ONLY mail
    ADD CONSTRAINT mail_pkey PRIMARY KEY (id);


--
-- Name: moder_book_cat_pkey; Type: CONSTRAINT; Schema: public; Owner: notabenoid; Tablespace: 
--

ALTER TABLE ONLY moder_book_cat
    ADD CONSTRAINT moder_book_cat_pkey PRIMARY KEY (book_id);


--
-- Name: notices_pkey; Type: CONSTRAINT; Schema: public; Owner: notabenoid; Tablespace: 
--

ALTER TABLE ONLY notices
    ADD CONSTRAINT notices_pkey PRIMARY KEY (id);


--
-- Name: orig_pkey; Type: CONSTRAINT; Schema: public; Owner: notabenoid; Tablespace: 
--

ALTER TABLE ONLY orig
    ADD CONSTRAINT orig_pkey PRIMARY KEY (id);


--
-- Name: reg_invites_pkey; Type: CONSTRAINT; Schema: public; Owner: notabenoid; Tablespace: 
--

ALTER TABLE ONLY reg_invites
    ADD CONSTRAINT reg_invites_pkey PRIMARY KEY (id);


--
-- Name: remind_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: notabenoid; Tablespace: 
--

ALTER TABLE ONLY remind_tokens
    ADD CONSTRAINT remind_tokens_pkey PRIMARY KEY (user_id);


--
-- Name: translate_pkey; Type: CONSTRAINT; Schema: public; Owner: notabenoid; Tablespace: 
--

ALTER TABLE ONLY translate
    ADD CONSTRAINT translate_pkey PRIMARY KEY (id);


--
-- Name: users_pkey; Type: CONSTRAINT; Schema: public; Owner: notabenoid; Tablespace: 
--

ALTER TABLE ONLY users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: blog_posts_book_id; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE INDEX blog_posts_book_id ON blog_posts USING btree (book_id, topics);


--
-- Name: bookmarks_book_id_idx; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE INDEX bookmarks_book_id_idx ON bookmarks USING btree (book_id);


--
-- Name: bookmarks_user_id_book_id_orig_id_idx; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE UNIQUE INDEX bookmarks_user_id_book_id_orig_id_idx ON bookmarks USING btree (user_id, book_id, orig_id);


--
-- Name: books_cat_id_idx; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE INDEX books_cat_id_idx ON books USING btree (cat_id);


--
-- Name: books_owner_id_idx; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE INDEX books_owner_id_idx ON books USING btree (owner_id);


--
-- Name: catalog_id; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE INDEX catalog_id ON catalog USING btree (pid);


--
-- Name: catalog_mp; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE INDEX catalog_mp ON catalog USING btree (mp);


--
-- Name: chapters_book_id; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE INDEX chapters_book_id ON chapters USING btree (book_id);


--
-- Name: comments_orig_id; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE INDEX comments_orig_id ON comments USING btree (orig_id);


--
-- Name: comments_post_id; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE INDEX comments_post_id ON comments USING btree (post_id);


--
-- Name: comments_rating_comment_id_user_id_idx; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE UNIQUE INDEX comments_rating_comment_id_user_id_idx ON comments_rating USING btree (comment_id, user_id);


--
-- Name: comments_user_id; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE INDEX comments_user_id ON comments USING btree (user_id);


--
-- Name: dict_book_id; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE INDEX dict_book_id ON dict USING btree (book_id);


--
-- Name: download_log_chap_id_ip_via_idx; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE UNIQUE INDEX download_log_chap_id_ip_via_idx ON download_log USING btree (chap_id, ip, via);


--
-- Name: group_queue_book_id; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE INDEX group_queue_book_id ON group_queue USING btree (book_id);


--
-- Name: group_queue_pk; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE UNIQUE INDEX group_queue_pk ON group_queue USING btree (user_id, book_id);


--
-- Name: groups_book_id; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE INDEX groups_book_id ON groups USING btree (book_id);


--
-- Name: groups_pk; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE UNIQUE INDEX groups_pk ON groups USING btree (user_id, book_id);


--
-- Name: invites_book_id; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE INDEX invites_book_id ON invites USING btree (book_id);


--
-- Name: invites_to_uid; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE UNIQUE INDEX invites_to_uid ON invites USING btree (to_uid, book_id);


--
-- Name: karma_rates_from_uid; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE INDEX karma_rates_from_uid ON karma_rates USING btree (from_uid);


--
-- Name: karma_rates_pk; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE UNIQUE INDEX karma_rates_pk ON karma_rates USING btree (to_uid, from_uid);


--
-- Name: languages_typ_idx; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE INDEX languages_typ_idx ON languages USING btree (typ);


--
-- Name: mail_user_id_folder_idx; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE INDEX mail_user_id_folder_idx ON mail USING btree (user_id, folder);


--
-- Name: marks_pk; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE UNIQUE INDEX marks_pk ON marks USING btree (tr_id, user_id);


--
-- Name: notices_user_id; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE INDEX notices_user_id ON notices USING btree (user_id, seen);


--
-- Name: orig_chap_id; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE INDEX orig_chap_id ON orig USING btree (chap_id);


--
-- Name: orig_old_id_chap_id_old_id_idx; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE INDEX orig_old_id_chap_id_old_id_idx ON orig_old_id USING btree (chap_id, old_id);


--
-- Name: poll_answers_poll_id_user_id_idx; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE INDEX poll_answers_poll_id_user_id_idx ON poll_answers USING btree (poll_id, user_id);


--
-- Name: recalc_log_book_id_idx; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE INDEX recalc_log_book_id_idx ON recalc_log USING btree (book_id);


--
-- Name: reg_invites_from_id_idx; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE INDEX reg_invites_from_id_idx ON reg_invites USING btree (from_id);


--
-- Name: search_history_lower_idx; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE INDEX search_history_lower_idx ON search_history USING btree (lower((request)::text));


--
-- Name: seen_orig_id; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE UNIQUE INDEX seen_orig_id ON seen USING btree (orig_id, user_id);


--
-- Name: seen_post_id; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE UNIQUE INDEX seen_post_id ON seen USING btree (post_id, user_id);


--
-- Name: seen_user_id; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE INDEX seen_user_id ON seen USING btree (user_id);


--
-- Name: tmp_dllog_chap_id_dat_idx; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE INDEX tmp_dllog_chap_id_dat_idx ON tmp_dllog USING btree (chap_id, dat);


--
-- Name: translate_book_id; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE INDEX translate_book_id ON translate USING btree (book_id);


--
-- Name: translate_chap_id; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE INDEX translate_chap_id ON translate USING btree (chap_id);


--
-- Name: translate_orig_id; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE INDEX translate_orig_id ON translate USING btree (orig_id);


--
-- Name: translate_user_id; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE INDEX translate_user_id ON translate USING btree (user_id);


--
-- Name: user_tr_stat_book_id; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE INDEX user_tr_stat_book_id ON user_tr_stat USING btree (book_id);


--
-- Name: user_tr_stat_user_id; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE UNIQUE INDEX user_tr_stat_user_id ON user_tr_stat USING btree (user_id, book_id);


--
-- Name: userinfo_pk; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE UNIQUE INDEX userinfo_pk ON userinfo USING btree (user_id, prop_id);


--
-- Name: users_login_idx; Type: INDEX; Schema: public; Owner: notabenoid; Tablespace: 
--

CREATE UNIQUE INDEX users_login_idx ON users USING btree (lower((login)::text));


--
-- Name: ban_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY ban
    ADD CONSTRAINT ban_user_id_fkey FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: blog_posts_book_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY blog_posts
    ADD CONSTRAINT blog_posts_book_id_fkey FOREIGN KEY (book_id) REFERENCES books(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: blog_posts_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY blog_posts
    ADD CONSTRAINT blog_posts_user_id_fkey FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: book_ban_reasons_book_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY book_ban_reasons
    ADD CONSTRAINT book_ban_reasons_book_id_fkey FOREIGN KEY (book_id) REFERENCES books(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: bookmarks_book_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY bookmarks
    ADD CONSTRAINT bookmarks_book_id_fkey FOREIGN KEY (book_id) REFERENCES books(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: bookmarks_orig_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY bookmarks
    ADD CONSTRAINT bookmarks_orig_id_fkey FOREIGN KEY (orig_id) REFERENCES orig(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: bookmarks_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY bookmarks
    ADD CONSTRAINT bookmarks_user_id_fkey FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: books_cat_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY books
    ADD CONSTRAINT books_cat_id_fkey FOREIGN KEY (cat_id) REFERENCES catalog(id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: books_owner_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY books
    ADD CONSTRAINT books_owner_id_fkey FOREIGN KEY (owner_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: books_s_lang_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY books
    ADD CONSTRAINT books_s_lang_fkey FOREIGN KEY (s_lang) REFERENCES languages(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: books_t_lang_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY books
    ADD CONSTRAINT books_t_lang_fkey FOREIGN KEY (t_lang) REFERENCES languages(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: catalog_pid_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY catalog
    ADD CONSTRAINT catalog_pid_fkey FOREIGN KEY (pid) REFERENCES catalog(id) ON DELETE RESTRICT;


--
-- Name: chapters_book_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY chapters
    ADD CONSTRAINT chapters_book_id_fkey FOREIGN KEY (book_id) REFERENCES books(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: comments_orig_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY comments
    ADD CONSTRAINT comments_orig_id_fkey FOREIGN KEY (orig_id) REFERENCES orig(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: comments_pid_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY comments
    ADD CONSTRAINT comments_pid_fkey FOREIGN KEY (pid) REFERENCES comments(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: comments_post_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY comments
    ADD CONSTRAINT comments_post_id_fkey FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: comments_rating_comment_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY comments_rating
    ADD CONSTRAINT comments_rating_comment_id_fkey FOREIGN KEY (comment_id) REFERENCES comments(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: comments_rating_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY comments_rating
    ADD CONSTRAINT comments_rating_user_id_fkey FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: comments_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY comments
    ADD CONSTRAINT comments_user_id_fkey FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: dict_book_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY dict
    ADD CONSTRAINT dict_book_id_fkey FOREIGN KEY (book_id) REFERENCES books(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: dict_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY dict
    ADD CONSTRAINT dict_user_id_fkey FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: group_queue_book_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY group_queue
    ADD CONSTRAINT group_queue_book_id_fkey FOREIGN KEY (book_id) REFERENCES books(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: group_queue_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY group_queue
    ADD CONSTRAINT group_queue_user_id_fkey FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: groups_book_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY groups
    ADD CONSTRAINT groups_book_id_fkey FOREIGN KEY (book_id) REFERENCES books(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: groups_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY groups
    ADD CONSTRAINT groups_user_id_fkey FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: invites_book_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY invites
    ADD CONSTRAINT invites_book_id_fkey FOREIGN KEY (book_id) REFERENCES books(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: invites_from_uid_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY invites
    ADD CONSTRAINT invites_from_uid_fkey FOREIGN KEY (from_uid) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: invites_to_uid_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY invites
    ADD CONSTRAINT invites_to_uid_fkey FOREIGN KEY (to_uid) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: karma_rates_from_uid_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY karma_rates
    ADD CONSTRAINT karma_rates_from_uid_fkey FOREIGN KEY (from_uid) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: karma_rates_to_uid_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY karma_rates
    ADD CONSTRAINT karma_rates_to_uid_fkey FOREIGN KEY (to_uid) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: marks_tr_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY marks
    ADD CONSTRAINT marks_tr_id_fkey FOREIGN KEY (tr_id) REFERENCES translate(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: marks_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY marks
    ADD CONSTRAINT marks_user_id_fkey FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: moder_book_cat_book_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY moder_book_cat
    ADD CONSTRAINT moder_book_cat_book_id_fkey FOREIGN KEY (book_id) REFERENCES books(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: notices_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY notices
    ADD CONSTRAINT notices_user_id_fkey FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: orig_chap_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY orig
    ADD CONSTRAINT orig_chap_id_fkey FOREIGN KEY (chap_id) REFERENCES chapters(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: poll_answers_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY poll_answers
    ADD CONSTRAINT poll_answers_user_id_fkey FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: reg_invites_from_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY reg_invites
    ADD CONSTRAINT reg_invites_from_id_fkey FOREIGN KEY (from_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: reg_invites_to_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY reg_invites
    ADD CONSTRAINT reg_invites_to_id_fkey FOREIGN KEY (to_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: remind_tokens_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY remind_tokens
    ADD CONSTRAINT remind_tokens_user_id_fkey FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: seen_orig_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY seen
    ADD CONSTRAINT seen_orig_id_fkey FOREIGN KEY (orig_id) REFERENCES orig(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: seen_post_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY seen
    ADD CONSTRAINT seen_post_id_fkey FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: seen_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY seen
    ADD CONSTRAINT seen_user_id_fkey FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: translate_chap_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY translate
    ADD CONSTRAINT translate_chap_id_fkey FOREIGN KEY (chap_id) REFERENCES chapters(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: translate_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY translate
    ADD CONSTRAINT translate_user_id_fkey FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: userinfo_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY userinfo
    ADD CONSTRAINT userinfo_user_id_fkey FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: users_invited_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY users
    ADD CONSTRAINT users_invited_by_fkey FOREIGN KEY (invited_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: users_lang_fkey; Type: FK CONSTRAINT; Schema: public; Owner: notabenoid
--

ALTER TABLE ONLY users
    ADD CONSTRAINT users_lang_fkey FOREIGN KEY (lang) REFERENCES languages(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: public; Type: ACL; Schema: -; Owner: postgres
--

REVOKE ALL ON SCHEMA public FROM PUBLIC;
REVOKE ALL ON SCHEMA public FROM postgres;
GRANT ALL ON SCHEMA public TO postgres;
GRANT ALL ON SCHEMA public TO PUBLIC;


--
-- PostgreSQL database dump complete
--

