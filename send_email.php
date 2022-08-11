<?php
/**
 * Откуда берутся ситуации is_valid = true, is_checked = false у email?
 * Стоит написать команду, чтобы пройтись по всей базе и чекнуть те emails, которые были добавлены ДО выкатки фичи
 */

declare(strict_types=1);

require_once 'redis.php';
require_once 'pgsql.php';

const REDIS_KEY_SEND_EMAIL = 'SEND_EMAIL';
const SEND_EMAIL_RPS_LIMIT = 10;

/**
 * Every hour
 * cron: 0 * * * * php send_expired_notifications.php
 *
 * @link https://developers.webasyst.ru/docs/installation/tips/cron/
 *
 * @return void
 */
function findExpiringSoonSubscriptions(): void
{
    $userIds = query(
        "
        SELECT user_id
        FROM users
        WHERE ts_subscription_expiration IS NOT NULL AND ts_subscription_expiration > (now() - interval '3 days') and status = 1 and subscription_status = 1
        "
    );

    foreach ($userIds as $userId) {
        $userEntity = query('SELECT * FROM users WHERE user_id = :user_id and status = 1', ['user_id' => $userId]);
        if (!$userEntity) {
            continue;
        }

        $userEmails = query(
            '
                SELECT user_id
                FROM user_emails us
                INNER JOIN emails e ON e.email_id = us.email_id
                WHERE user_id = user_id AND is_valid = true
            ',
            ['user_id' => $userId]
        );

        foreach ($userEmails as $userEmail) {
            //$queue->push($userEmail['user_id'], $userEmail['email_id], sprintf("%s, your subscription is expiring soon", $userEntity['username']));
        }
    }
}

/**
 * php-cli service
 *
 * @return void
 */
function sendEmailService(): void
{
    while (true) {
        $count = getRedis()->get(REDIS_KEY_SEND_EMAIL);
        if (SEND_EMAIL_RPS_LIMIT <= $count) {
            usleep(MINUTE * MILLISECONDS);
        }

        //$message = $queue->pop();
        $userId = null;
        $emailId = null;
        $message = '';

        $userEntity = query('SELECT * FROM users WHERE user_id = :user_id and status = 1', ['user_id' => $userId]);
        if (!$userEntity) {
            continue;
        }

        $emailEntity = query(
            '
                SELECT e.*
                FROM emails e
                INNER JOIN user_emails us ON us.email_id = e.email_id AND us.user_id = :user_id
                WHERE email_id = :email_id and status = 1
            ',
            [
                'email_id' => $emailId,
                'user_id' => $userEntity['user_id'],
            ]
        );

        if (!$emailEntity) {
            continue;
        }

        sendEmail(
            'info@ourcompany.com',
            $emailEntity['email'],
            'Our Company',
            $message,
        );

        setRPC(REDIS_KEY_SEND_EMAIL, MINUTE * MILLISECONDS);
    }
}

function sendEmail(string $from, string $to, string $subject, string $body): void
{
    //send request to Email provider
}
