<?php
/**
 * Created by PhpStorm.
 * User: Christoph
 * Date: 31.08.14
 * Time: 22:18
 */

class AutoComplete {
	function __construct($view) {
		error_reporting(-1);
		ini_set('display_errors', 'On');

		$this->db = $view->model->db;
		$this->tableId = 1;
	}

	function remote_insert_search_string() {
		$inputString = htmlspecialchars($_POST['inputString']);

		if (strlen($inputString) == 0) {
			return array(
				"code" => 1,
				"msg" => "Could not insert search string. Value is empty."
			);
		}

		// check if entry already exists
		$sth = $this->db->prepare("SELECT * FROM auto_complete WHERE input_string = :inputString");

		$sth->execute(array(
			":inputString" => $inputString
		));

		// entry exists - update weight
		if ($sth->rowCount() != 0) {
			$result = $sth->fetch();

			$sth = $this->db->prepare("UPDATE auto_complete SET weight = weight + 1 WHERE id = :id");

			$sth->execute(array(
				':id' => $result['id']
			));

			return array(
				"code" => 0,
				"msg" => "An existing search entry was successfully updated."
			);
		}

		// create new entry
		$sth = $this->db->prepare("INSERT INTO auto_complete (input_string, weight, last_searched) VALUES (:inputString, 1, NOW())");
		$sth->execute(array(
			':inputString' => $inputString
		));

		return array(
			"code" => 0,
			"msg" => "A new search entry was successfully created."
		);
	}

	function remote_get_auto_complete() {
		$inputString = htmlspecialchars($_POST['inputString']);

		if (strlen($inputString) == 0) {
			return array(
				"code" => 1,
				"msg" => "Could not get search results. Value is empty."
			);
		}

		$sth = $this->db->prepare("SELECT * FROM auto_complete WHERE input_string LIKE :inputString order by weight DESC, last_searched");
		$sth->execute(array(
			':inputString' => $inputString . "%"
		));

		return array(
			"code" => 0,
			"message" => "Successfully got search results.",
			"needleLength" => strlen($inputString),
			"results" => $sth->fetchAll()
		);
	}
}