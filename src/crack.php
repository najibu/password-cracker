<?php

require __DIR__ .'/database.php';

header('Content-Type: application/json');

$type = $_POST['type'] ?? 'easy';

define('SALT','ThisIs-A-Salt123');

function salter($string) {
	return md5($string . SALT);
}

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
    'easy' => easyFiveDigitNumbers($passwordList),
    'medium-uppercase' => mediumUppercaseLettersAndNumber($passwordList),
    'medium-lowercase' => mediumLowercaseDictionaryWords($passwordList),
    'hard' => hardSixCharacterMixedPasswords($passwordList),
    default => ['error' => 'Invalid type'],
};

function sampling(string $chars, int $size): Generator {
    yield from generateCombinations($chars, $size, []);
}

function generateCombinations(string $chars, int $size, array $prefix): Generator {
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

function isHashInPasswordList($hash, $passwordList) {
    $flippedPasswordList = array_flip($passwordList);

    return isset($flippedPasswordList[$hash]);
}

function easyFiveDigitNumbers($passwordList) {
    $combinations = sampling(digits(), 5);

    return findMatchingPasswords($passwordList, $combinations);
}

function digits() {
    return implode(',', range(0, 9));
}

function mediumUppercaseLettersAndNumber($passwordList) {
    $uppercaseLetters = implode(',', range('A', 'Z'));

    $combinations = sampling($uppercaseLetters . digits(), 4);

    return findMatchingPasswords($passwordList, $combinations);
}

function mediumLowercaseDictionaryWords($passwordList) {
    $dictionary = file('dictionary.txt', FILE_IGNORE_NEW_LINES);

    $results = [];

    foreach ($dictionary as $word) {
        if (strlen($word) <= 6) {
            $hashedPassword = salter(strtolower($word));

            if (isHashInPasswordList($hashedPassword, $passwordList)) {
                $results[] = [
                    "user_id" => array_search($hashedPassword, $passwordList),
                    "password" => $hashedPassword,
                    "actual_password" => $word
                ];
            }
        }
    }

    return $results;
}

function hardSixCharacterMixedPasswords($passwordList) {
    $chars = implode(',', range('A', 'Z')) . ',' . implode(',', range('a', 'z')) . ',' . digits();
    $combinations = sampling($chars, 6);

    return findMatchingPasswords($passwordList, $combinations);
}


function findMatchingPasswords($passwordList, $combinations) {
    $results = [];

    foreach ($combinations as $combination) {
        $hashedPassword = salter($combination);

        if (isHashInPasswordList($hashedPassword, $passwordList)) {
            $results[] = [
                "user_id" => array_search($hashedPassword, $passwordList),
                "password" => $hashedPassword,
                "actual_password" => $combination
            ];
        }
    }

    return $results;
}

echo json_encode($results);
