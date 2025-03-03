<?php

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;


$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

header('Content-Type: application/json');

$type = $_POST['type'] ?? 'easy';

define('SALT','ThisIs-A-Salt123');
function salter($string) {
	return md5($string . SALT);
}

try {
    $dsn = 'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'];
    $user = $_ENV['DB_USER'];
    $password = $_ENV['DB_PASS'];

    $passwordList = [];

    $dbConnection = new PDO($dsn, $user, $password);
    $dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $dbConnection->prepare('select user_id, password from not_so_smart_users');
    $stmt->execute();

    $result = $stmt->setFetchMode(PDO::FETCH_ASSOC);

    foreach ($stmt->fetchAll() as $key => $value) {
        $passwordList[$value["user_id"]] = $value["password"];
    }

} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}



$results = [];

switch ($type) {
    case 'easy':
        $results = easyFiveDigitNumbers($passwordList);
        break;
    case 'medium-uppercase':
        $results = mediumUppercaseLettersAndNumber($passwordList);
        break;
    case 'medium-lowercase':
         $results = mediumLowercaseDictionaryWords($passwordList);
        break;
    case 'hard':
        $results = hardSixCharacterMixedPasswords($passwordList);
        break;

    default:
        echo json_encode(['error' => 'Invalid type']);
        break;
}

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
