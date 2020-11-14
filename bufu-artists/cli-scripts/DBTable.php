<?php

/**
 * Created by PhpStorm.
 * User: towangfoo
 * Date: 11.09.20
 * Time: 13:33
 */
class DBTable
{
	/**
	 * @var string
	 */
	private $table;

	/**
	 * @var array
	 */
	private $connectionParams = [];

	/**
	 * @var stdClass
	 */
	private $itemTemplate;

	/**
	 * @var PDO
	 */
	private $connection;

	public function __construct($tableName, array $connectionParams)
	{
		$this->table = $tableName;
		$this->connectionParams = $connectionParams;
	}

	public function setHydrateObject(stdClass $object)
	{
		$this->itemTemplate = $object;
	}

	/**
	 * @return stdClass[]
	 */
	public function getRows($query = null)
	{
		$connection = $this->getConnection();

		if ( null === $query ) {
			$query = sprintf("SELECT * FROM %s", $this->table);
		}
		else {
			$query = str_replace("%TABLE%", $this->table, $query);
		}

		$stmt = $connection->prepare($query);
		$stmt->execute();

		$rows = [];
		while ($row = $stmt->fetch()) {
			$rows[] = $this->hydrateItem($row);
		}

		return $rows;
	}

	/**
	 * @param DateTime $dateTime
	 * @param string $columnName
	 * @return stdClass[]
	 */
	public function getRowsNewerThan(DateTime $dateTime, $columnName = 'datum')
	{
		$timestamp = $dateTime->getTimestamp();

		$connection = $this->getConnection();

		$query = sprintf("SELECT * FROM %s WHERE `%s` > :timestamp", $this->table, $columnName);

		$stmt = $connection->prepare($query);
		$stmt->execute(['timestamp' => $timestamp]);

		$rows = [];
		while ($row = $stmt->fetch()) {
			$rows[] = $this->hydrateItem($row);
		}

		return $rows;
	}

	/**
	 * @return PDO
	 * @throws PDOException
	 */
	private function getConnection()
	{
		if (!$this->connection) {
			$host = $this->connectionParams['hostname'];
			$port = 3306;
			$dbName = $this->connectionParams['db'];
			$username = $this->connectionParams['username'];
			$password = $this->connectionParams['password'];
			$options = (array_key_exists('db_options', $this->connectionParams)) ? $this->connectionParams['db_options'] : [];

			$dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";

			$defaultOptions = [
				PDO::ATTR_EMULATE_PREPARES   => false, // turn off emulation mode for "real" prepared statements
				PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, //turn on errors in the form of exceptions
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, //make the default fetch be an associative array
			];

			$this->connection = new PDO($dsn, $username, $password, array_merge($defaultOptions, $options));
		}

		return $this->connection;
	}

	/**
	 * @param array $data
	 * @return \stdClass
	 */
	private function hydrateItem(array $data)
	{
		$item = clone $this->itemTemplate;

		foreach (array_keys(get_object_vars($item)) as $prop) {
			if (array_key_exists($prop, $data)) {
				$item->$prop = $data[$prop];
			}
		}

		return $item;
	}
}