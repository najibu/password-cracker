<?php

ini_set('memory_limit', '256M'); // Increase memory limit to 256MB
set_time_limit(120); // Increase time limit to 120 seconds

require __DIR__ .'/config.php';

header('Content-Type: application/json');

$type = $_POST['type'] ?? 'hard';


$passwordList = [];

try {
    $stmt = $dbConnection->prepare('SELECT user_id, password FROM not_so_smart_users');
    $stmt->execute();

    $result = $stmt->setFetchMode(PDO::FETCH_ASSOC);

    foreach ($stmt->fetchAll() as $key => $value) {
        $passwordList[$value["user_id"]] = $value["password"];
    }

} catch (PDOException $e) {
    echo json_encode(['error' => 'Query failed: ' . $e->getMessage()]);
    exit;
}


$results = match ($type) {
    'easy' => crackNumbers($passwordList),
    'medium-uppercase' => crackUppercaseAndNumber($passwordList),
    'medium-lowercase' => crackDictionary($passwordList),
    'hard' => crackAlphanumeric($passwordList),
    default => ['error' => 'Invalid type'],
};

function sampling(string $chars, int $size, int $batchSize = 10_000): Generator
{
    $batch = [];
    foreach (generateCombinations($chars, $size, []) as $combination) {
        $batch[] = $combination;
        if (count($batch) >= $batchSize) {
            yield $batch;
            $batch = [];
        }
    }

    if (!empty($batch)) {
        yield $batch;
    }
}

function generateCombinations(string $chars, int $size, array $prefix): Generator
{
    if ($size === 0) {
        yield implode('', $prefix);
        return;
    }

    foreach (explode(',', $chars) as $char) {
        $prefix[] = $char;
        yield from generateCombinations($chars, $size - 1, $prefix);
        array_pop($prefix);
    }
}

function crackNumbers($passwordList)
{
    $combinations = sampling(digits(), 5);

    return findMatchingPasswords($passwordList, $combinations);
}

function digits(): string
{
    return implode(',', range(0, 9));
}

function uppercaseLetters(): string
{
    return implode(',', range('A', 'Z'));
}

function lowercaseLetters(): string
{
    return implode(',', range('a', 'z'));
}

function crackUppercaseAndNumber($passwordList)
{
    $combinations = sampling(uppercaseLetters() . digits(), 4);

    return findMatchingPasswords($passwordList, $combinations);
}

function crackDictionary($passwordList)
{
    $dictionary = file('dictionary.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $flippedPasswordList = array_flip($passwordList);

    $results = [];

    foreach ($dictionary as $word) {
        if (strlen($word) <= 6) {
            $hash = salter(strtolower($word));

            if (isset($flippedPasswordList[$hash])) {
                $results[] = [
                    "user_id" => array_search($hash, $passwordList, true),
                    "password" => $hash,
                    "actual_password" => $word
                ];
            }
        }
    }

    return $results;
}

function crackAlphanumeric($passwordList)
{
    $chars = uppercaseLetters() . ',' . lowercaseLetters() . ',' . digits();
    $combinations = sampling($chars, 6, 50_000);

    return findMatchingPasswords($passwordList, $combinations);
}


function findMatchingPasswords($passwordList, $combinations)
{
    $results = [];
    $flippedPasswordList = array_flip($passwordList);

    foreach ($combinations as $batch) {
        foreach ($batch as $combination) {
            $hash = salter($combination);

            if (isset($flippedPasswordList[$hash])) {
                $results[] = [
                    "user_id" => array_search($hash, $passwordList, true),
                    "password" => $hash,
                    "actual_password" => $combination
                ];
            }
        }
        unset($batch); // Clear the batch from memory
    }
    unset($flippedPasswordList); // Clear the flipped password list from memory

    return $results;
}

echo json_encode($results);
