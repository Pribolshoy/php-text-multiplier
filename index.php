<?php
require_once './safemysql.class.php';
require_once './textparser.class.php';

try {
    $db = new SafeMysql(array('user' => 'root', 'pass' => '','db' => 'test', 'charset' => 'utf8'));
    $db->query(
        'CREATE TABLE IF NOT EXISTS `content` (
		`id` INT(5) NOT NULL auto_increment PRIMARY KEY,
		`content` TEXT NOT NULL,
		`hash` VARCHAR(50) NOT NULL,
		UNIQUE INDEX `hash` (`hash`)
	)
	COLLATE="utf8mb4_unicode_ci" ENGINE=InnoDB;'
    );
} catch (Exception $e) {
    print $e->getMessage();
    die();
}

$text = "Товарищи!< С другой стороны::< Равным:: Таким > образом> практика показывает, что <реализация <намеченных заданий::развития <<организационной::форм> деятельности::обучения кадров:: плановых заданий>>::постоянный рост активности> требует от нас анализа <новых предложений::<финансовых::административных> условий:: поставленных <задач::целей>>.";

$multiplier = new TextParser();
$result = $multiplier->run($text)->getResult();
// $multiplier->showStat(); // показать детали
if ($result) {
    $i=0;
    foreach ($result as $value) {
        $hash = md5($value);
        // $result = $db->getOne("SELECT id FROM `content` WHERE `hash` = ?s ", $hash);
        // if (!$result) {
            // $db->query("INSERT INTO `content` SET `content` = ?s, `hash` = ?s ", $value, $hash);
            // $i++;
        // }
        $db->query("INSERT IGNORE INTO `content` SET `content` = ?s, `hash` = ?s ", $value, $hash);
        $i += $db->affectedRows();
    }

    print "В БД добавлено $i новых строк";
}
