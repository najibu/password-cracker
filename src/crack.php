<?php

declare(strict_types=1);

require __DIR__ .'/database/connect.php';

header('Content-Type: application/json');

$type = $_POST['type'] ?? 'easy';


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

/**
 * Generate batches of character combinations
 *
 * @param string $chars Characters to use in combinations
 * @param int $size Size of each combination
 * @param int $batchSize Number of combinations per batch
 * @return \Generator<array<string>> Batches of combinations
 */
function sampling(string $chars, int $size, int $batchSize = 5_000): \Generator
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

/**
 * Generate all combinations of characters
 *
 * @param string $chars Characters to use in combinations
 * @param int $size Remaining size of combinations to generate
 * @param array<string> $prefix Current prefix for combination
 * @return \Generator<string> Generated combinations
 */
function generateCombinations(string $chars, int $size, array $prefix): \Generator
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

/**
 * Get a comma-separated string of digits 0-9
 *
 * @return string Comma-separated digits
 */
function digits(): string
{
    return implode(',', range(0, 9));
}

/**
 * Get a comma-separated string of uppercase letters A-Z
 *
 * @return string Comma-separated uppercase letters
 */
function uppercaseLetters(): string
{
    return implode(',', range('A', 'Z'));
}

/**
 * Get a comma-separated string of lowercase letters a-z
 *
 * @return string Comma-separated lowercase letters
 */
function lowercaseLetters(): string
{
    return implode(',', range('a', 'z'));
}

/**
 * Crack passwords that are 5-digit numbers
 *
 * @param array<int, string> $passwordList Hash-to-user mapping
 * @return array<array<string, mixed>> Cracked password results
 */
function crackNumbers(array $passwordList): array
{
    $combinations = sampling(digits(), 5);

    return findMatchingPasswordsWithLimit($passwordList, $combinations);
}

/**
 * Crack passwords that are 3 uppercase letters and 1 number
 *
 * @param array<int, string> $passwordList Hash-to-user mapping
 * @return array<array<string, mixed>> Cracked password results
 */
function crackUppercaseAndNumber(array $passwordList): array
{
    $combinations = sampling(uppercaseLetters() . digits(), 4);

    return findMatchingPasswordsWithLimit($passwordList, $combinations);
}

/**
 * Crack passwords that are dictionary words up to 6 characters
 *
 * @param array<int, string> $passwordList Hash-to-user mapping
 * @return array<array<string, mixed>> Cracked password results
 */
function crackDictionary(array $passwordList): array
{
    $results = [];
    $dictionaryFile = 'wordlist/dictionary.txt';
    $flippedPasswordList = array_flip($passwordList);

    if (file_exists($dictionaryFile)) {
        $dictionary = file($dictionaryFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($dictionary as $word) {
            if (strlen($word) <= 6) {
                $hash = salter(strtolower($word));

                if (isset($flippedPasswordList[$hash])) {
                    $results[] = [
                        "user_id" => array_search($hash, $passwordList, true),
                        "password" => $hash,
                        "actual_password" => $word
                    ];

                    // Early termination once we have enough results
                    if (count($results) >= 12) {
                        return $results;
                    }
                }
            }
        }
    }

    return $results;
}

/**
 * Crack complex passwords with mixed case and numbers
 *
 * @param array<int, string> $passwordList Hash-to-user mapping
 * @return array<array<string, mixed>> Cracked password results
 */
function crackAlphanumeric(array $passwordList): array
{
    $results = [];
    $maxResults = 2; // Limit the number of results

    $combinations = generateAbC12zPattern();
    $results = findMatchingPasswordsWithLimit(
        $passwordList,
        $combinations,
        $maxResults
    );

    return $results;
}

/**
 * Generate pattern-specific password combinations
 *
 * @param int $batchSize Number of combinations per batch
 * @return \Generator<array<string>> Batches of pattern combinations
 */
function generateAbC12zPattern(int $batchSize = 5_000): \Generator
{
    $batch = [];
    $uppercase = explode(',', uppercaseLetters());
    $lowercase = explode(',', lowercaseLetters());
    $digits = explode(',', digits());

    foreach ($uppercase as $first) {
        foreach ($lowercase as $second) {
            foreach ($uppercase as $third) {
                foreach ($digits as $fourth) {
                    foreach ($digits as $fifth) {
                        foreach ($lowercase as $sixth) {
                            $batch[] = $first . $second . $third . $fourth . $fifth . $sixth;

                            if (count($batch) >= $batchSize) {
                                yield $batch;
                                $batch = [];
                            }
                        }
                    }
                }
            }
        }
    }

    if (!empty($batch)) {
        yield $batch;
    }
}

/**
 * Find matching passwords in batches with a limit
 *
 * @param array<int, string> $passwordList Hash-to-user mapping
 * @param \Generator<array<string>> $combinations Batches of password combinations
 * @param int $limit Maximum number of matches to return
 * @return array<array<string, mixed>> Matching password results
 */
function findMatchingPasswordsWithLimit(array $passwordList, \Generator $combinations, int $limit = 4): array
{
    $results = [];
    $flippedPasswordList = array_flip($passwordList);

    foreach ($combinations as $batch) {
        foreach ($batch as $combination) {
            $hash = salter($combination);

            if (isset($flippedPasswordList[$hash])) {
                $results[] = [
                    'user_id' => array_search($hash, $passwordList, true),
                    'password' => $hash,
                    'actual_password' => $combination,
                ];

                // Early termination once we have enough results
                if (count($results) >= $limit) {
                    return $results;
                }
            }
        }
        unset($batch); // Clear the batch from memory
    }

    return $results;
}

echo json_encode($results);
