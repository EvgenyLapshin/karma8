<?php

declare(strict_types=1);

function setEmail(array $userEntity, string $email): bool
{
    // Проверка ворованной регуляркой из stackoverflow и конечно покрытая тестами
    if (!preg_match('~^[^@]+@[^@]+\.[^@]+$~', $email)) {
        throw new RuntimeException('This email is not valid');
    }

    $emailEntity = findValidEmail($email);
    if ($emailEntity) {
        $userEmailEntity = query('SELECT COUNT(*) FROM user_emails WHERE user_id = :user_id, email_id = :email_id', ['user_id' => $userEntity['user_id'], 'email_id' => $emailEntity['email_id']]);
        if ($userEmailEntity) {
            if (!$emailEntity['is_checked']) {
                // закинуть в after worker checkEmailThroughProvider($emailEntity);
            }

            throw new RuntimeException('This email already added');
        }
    } else {
        $emailEntity = query('INSERT emails SET email = :email', ['email' => $email]);
        query('INSERT user_emails SET user_id = :user_id, email_id = :email_id', ['user_id' => $userEntity['user_id'], $emailEntity['email_id']]);
    }

    // закинуть в after worker checkEmailThroughProvider($emailEntity);

    return true;
}

function findValidEmail(string $email): array
{
    $emailEntity = query('SELECT * FROM email WHERE email = :email', [':email' => $email]);
    if (!isset($emailEntity)) {
        return [];
    }

    if (!$emailEntity['is_valid'] && $emailEntity['is_checked']) {
        throw new RuntimeException('This email is not valid');
    }

    return $emailEntity;
}

function checkEmailThroughProvider(array $emailEntity): void
{
    $isChecked = (new EmailProvider())->checkEmail($emailEntity['email']);
    query(
        'INSERT user_emails SET is_valid = :is_valid, is_checked = :is_checked WHERE email_id = :email_id',
        [
            'is_valid' => $isChecked,
            'is_checked' => true,
            'email_id' => ':email_id'
        ]
    );
}
