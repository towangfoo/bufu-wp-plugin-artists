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
	public function getRows()
	{
//		$connection = $this->getConnection();
//
//		$query = sprintf("SELECT * FROM %s", $this->table);
//
//		$stmt = $connection->prepare($query);
//		$stmt->execute();
//
//		$rows = [];
//		while ($row = $stmt->fetch()) {
//			$rows[] = $this->hydrateItem($row);
//		}
//
//		return $rows;

		$test = new stdClass();
		$test->id = 1;
		$test->artistname = "Gerhard Schöne";
		$test->sortierung = "schöne";
		$test->profiltext = "Der populärste und produktivste ostdeutsche Liedermacher (geb. 1952 in Coswig) ist heute längst in allen Himmelsrichtungen ein hochgeschätzter Künstler, stammt aus einer sächsichen vielköpfigen Pfarrersfamilie,  mit 11 Jahren Geigenunterricht, 1965 erste Erfolge mit selbst gedichteten Parodien  zwischen Junge Talente und Junger Gemeinde, 1968 Lehre als Korpusgürtler, danach hauptberuflicher Laienschauspieler in der Leipziger Spielgemeinde, danach Briefträger in Coswig, Abendstudium an der Musikhoch-schule in Dresden.

Seine erste LP Spar deinen Wein für morgen (1981) und seine Kinderalben (Kinderland und Kinderlieder aus aller Welt) machen ihn rasch innerhalb und über die Grenzen der DDR bekannt (seine Platten für Kinder sind eines der Lieblingsgeschenke an die staunende West-Verwandschaft). 

1988 mit Lart de passage große open air- Tour Du hast es nur noch nicht probiert mit bis zu 12 000 Besuchern(Insel der Jugend), nach 1990 mehr als ein Dutzend neue Alben und unterwegs mit verschiedenen, häufig theatralisch inszenierten und inspirierten Programmen ( u.a. Die sieben Gaben, Die Sammlung des blinden Herrn Stein, Das Perlhuhn im Schnee, Die Lieder der Fotografen, Könige aus Morgenland, Die blaue Ampel, Denn Jule schläft fast nie).
   
Schöne liebt die Verwandlung, doch bleibt er dabei sich und seinem Publikum auf innovative Weise immer treu.
Der Preis der Deutschen Schallplattenkritik 1992, 2003 und 2010, der Leopold- Medienpreis 1995,
Preis der Stiftung Bibel und Kultur (nach John Neumeier). die Gema-Auszeichnung mit dem Deutschen Musikautorenpreis sowie der Preis des sächsischen Musikrates 2016 belegen nachhaltig, dass sein Liedschaffen wie Auftreten nicht nur unter seinem Publikum hohe Wertschätzung entgegen gebracht wird. 

Schöne ist UNICEF-Botschafter.  

Bei BuschFunk - durchaus sein Hauslabel -sind seit 1991 mehr als fünfundzwanzig CD, DVD und fünf Songbücher veröffentlich. Der Verlag organisert auch seine Konzerte seit 1991, im Durchschnitt etwas über einhundert im Jahr. 

Gleich mit zwei neuen Musikproduktionen (Ein Tag im Leben eines Kindes - gemeinsam mit dem Gewandhauskinderchor- und dem Album \"Komm herein in das Haus - gemeinsam mit dem Organisten Jens Goldhardt und Ralf Benschu an Saxophon und Klarinette) belegt Schöne im Kontext seines 65.Geburtstag, dass der Ruhestand seine Sache nicht ist.
";
		$test->homepage = "http://www.gerhardschoene.de";
		$test->shoplinks = "[##TYPE cd ##URL http://konsum.buschfunk.com/lieder-aus-dem-kinderland.html ##LABEL Lieder aus dem Kinderland] [##TYPE cd ##URL http://konsum.buschfunk.com/boses-baby-kitty-schmidt.html ##LABEL Böses Baby Kitty Schmidt] [##TYPE buch ##URL http://konsum.buschfunk.com/ich-bin-ein-gast-auf-erden-taschenbuch.html ##LABEL Ich bin ein Gast auf Erden] [##TYPE cd ##URL http://konsum.buschfunk.com/ich-offne-die-tur-weit-am-abend.html ##LABEL Ich öffne die Tür weit am Abend] [##TYPE cd ##URL http://konsum.buschfunk.com/die-sieben-gaben-lieder-im-marchenmantel.html ##LABEL Die sieben Gaben] [##TYPE anderes ##URL http://buschfunk.bandcamp.com/?filter_band=3862060720 ##LABEL ZUM DOWNLOAD MP3] [##TYPE cd ##URL http://konsum.buschfunk.com/ein-tag-im-leben-eines-kindes.html ##LABEL Ein Tag im Leben eines Kindes] [##TYPE anderes ##URL http://konsum.buschfunk.com/konzertkarten/konzertkarte-gerhard-schone.html ##LABEL KONZERTKARTEN] ";

		return [$test];
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

			$dsn = "mysql:host={$host};port={$port};dbname={$dbName}";

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