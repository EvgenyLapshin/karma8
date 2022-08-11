CREATE TABLE IF NOT EXISTS users (
    user_id serial PRIMARY KEY,
    username varchar unique not null,
    email varchar unique not null,
    email_confirmed bool default false not null,
    email_verification_code varchar,
    ts_email_verification_code_created timestamp,
    subscription_status smallint,  -- 0 free, 1 active, 2 expired
    ts_subscription_expiration timestamp,
    status smallint default 1 not null, -- 0 deleted, 1 active
    ts_created timestamp default now() not null,
);

-- при условии, что у нас много подписок
CREATE INDEX users_ts_subscription_expiration_index (ts_subscription_expiration) WHERE (status = 1 AND subscription_status = 1)

CREATE TABLE IF NOT EXISTS user_emails (
    user_id,
    email_id,
    PRIMARY KEY (user_id, email_id);
);

CREATE TABLE IF NOT EXISTS emails (
    email_id int PRIMARY KEY,
    email varchar unique,
    is_valid bool default false
    is_checked bool default false,
    ts_created timestamp default now() not null,
);
