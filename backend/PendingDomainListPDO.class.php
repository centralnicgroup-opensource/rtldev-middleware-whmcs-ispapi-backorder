<?php

namespace HEXONET;

use PDO as PDO;

/**
 * PendingDomainListPDO
 *
 * The PHP implementation of the pending domain list class using PDO.
 *
 * @author Hexonet GmbH
 *
 */
class PendingDomainListPDO
{
    //PDO instance
    private $instance;

    //The select request
    private $request;

    //The items limit
    private $limit;

    //PDO Driver
    private $driver;

    //The URL of the PendingDeleteList file
    const PENDING_DELETE_LIST_FILE_URL = "https://www.hexonet.net/files/domain-backordering/pending_delete_domain_list.csv.zip";

    /**
     * Constructor
     *
     * @param string $dsn The Data Source Name for the Database access.
     */
    public function __construct($dsn, $user = null, $pw = null)
    {
        $this->instance = $this->connect($dsn, $user, $pw);
        $this->driver = $this->instance->getAttribute(PDO::ATTR_DRIVER_NAME);
        $this->limit = 20;
        $this->request = "SELECT * FROM backorder_pending_domains #WHERE# #ORDERBY# #LIMIT#";
    }

    /**
     * Returns the connected PDO database instance.
     * Check if database tables exists if not, cretate it.
     *
     * @param string $dsn
     * @return PDO Database instance
     */
    public function connect($dsn, $user = null, $pw = null)
    {
        try {
            $instance = new PDO($dsn, $user, $pw);
        } catch (PDOException $e) {
            die($e->getMessage());
        }

        $instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $instance;
    }

    /**
     * Create the table.
     */
    public function createTable()
    {
        try {
            $this->instance->query("SELECT 1 FROM backorder_pending_domains LIMIT 1");
        } catch (Exception $e) {
            $this->instance->exec('CREATE TABLE backorder_pending_domains (
					domain_index int(11) NOT NULL AUTO_INCREMENT,
					zone varchar(7) NOT NULL,
					domain varchar(63) NOT NULL,
					drop_date datetime NOT NULL,
					domain_number_of_characters tinyint(4) NOT NULL,
					domain_number_of_hyphens tinyint(4) NOT NULL,
					domain_number_of_digits tinyint(4) NOT NULL,
					domain_number_of_umlauts tinyint(4) NOT NULL,
					PRIMARY KEY (domain_index),
					UNIQUE KEY domain (domain,zone),
					KEY drop_date (drop_date),
					KEY domain_number_of_characters (domain_number_of_characters),
					KEY domain_number_of_hyphens (domain_number_of_hyphens),
					KEY domain_number_of_digits (domain_number_of_digits),
					KEY domain_number_of_umlauts (domain_number_of_umlauts)
			)');
        }
    }

    /**
     * Drop the table.
     */
    public function dropTable()
    {
        try {
            $this->instance->exec('DROP TABLE backorder_pending_domains');
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }

    /**
     * Clear the table.
     */
    public function clearTable()
    {
        try {
            $this->instance->exec('DELETE FROM backorder_pending_domains');
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }

    /**
     * Download the PendingDeleteList file on Hexonet's website in the current working directory.
     */
    public function downloadPendingDeleteList()
    {
        set_time_limit(0);

        if (!isset($GLOBALS["downloads_dir"])) {
            die("Cannot find tmp directory!");
        }
        $download = $GLOBALS["downloads_dir"]."pending_delete_list_tmp.zip";
        $fp = fopen($download, 'w+');
        $ch = curl_init(str_replace(" ", "%20", self::PENDING_DELETE_LIST_FILE_URL));

        curl_setopt($ch, CURLOPT_TIMEOUT, 50);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //IMPORTANT in order to avoid displaying content in the browser.
        curl_setopt($ch, CURLOPT_FILE, $fp);

        $data = curl_exec($ch);
        curl_close($ch);
        //file_put_contents("../tmp/pending_delete_list_tmp.zip", fopen(self::PENDING_DELETE_LIST_FILE_URL, 'r'));
        //exec("wget -O ../tmp/pending_delete_list_tmp.zip ".self::PENDING_DELETE_LIST_FILE_URL);
    }

    /**
     * Import the Pending Delete List.
     * - If file given, import the file in the database
     * - Else download the file from the Hexonet's website and import it
     *
     * @param string $file Path of the file you want to import
     */
    public function import($tlds = array(), $file = null)
    {
        $values = "";
        $line = 0;

        if (isset($file)) {
            $handle = fopen($file, "r");
        } else {
            $this->downloadPendingDeleteList();
            $handle = fopen('zip://'.$GLOBALS["downloads_dir"].'pending_delete_list_tmp.zip#pending_delete_domain_list.csv', 'r');

            //throw an error message when the zip extension is missing
            if (empty($handle)) {
                die("Failed to import the drop list. Could it be that ZIP extension is not installed?");
            }
        }

        $sql = 'INSERT IGNORE INTO backorder_pending_domains (domain, zone, drop_date, domain_number_of_characters, domain_number_of_hyphens, domain_number_of_digits) VALUES ';

        if ($handle !== false) {
            $this->instance->beginTransaction();
            while (($data = fgetcsv($handle, 1000, ";")) !== false) {
                $line++;

                if (preg_match('/^([^.]*)\.(.*)$/', $data[0], $m)) {
                    $domain = strtolower($m[1]);
                    $zone = strtolower($m[2]);
                }

                $domain_number_of_hyphens = substr_count($domain, '-');
                $domain_number_of_digits = preg_match_all("/[0-9]/", $domain);
                //$domain_number_of_umlauts = preg_match_all( "/[äüö]/", utf8_encode($domain))/2;

                //EXCLUDE SOME TLDS
                /*if(!in_array($zone, $tlds)){
                    continue;
                }*/

                $values .= "('".$domain."', '".$zone."', '".$data[1]."', ".strlen($domain).", ".$domain_number_of_hyphens.", ".$domain_number_of_digits."),";

                if ($line == 500) {
                    $values = substr($values, 0, -1);
                    try {
                        $this->instance->exec($sql.$values);
                    } catch (PDOException $ex) {
                    }
                    $values = "";
                    $line = 0;
                }
            }
            $values = substr($values, 0, -1);
            try {
                $this->instance->exec($sql.$values);
            } catch (PDOException $ex) {
            }
            $this->instance->commit();
            fclose($handle);
        }

        //delete domains with drop_date in the past
        $this->instance->exec("DELETE FROM backorder_pending_domains WHERE drop_date < NOW()");
    }

    /**
     * Returns only the filtered domains. (% $string %)
     *
     * @param string $string The filter
     */
    public function setFilter($string)
    {
        $this->request =  preg_replace('/(#WHERE#)/i', 'WHERE domain like "'.$string.'"', $this->request);
    }


    /**
     * Set the order of the returned list.
     *
     * @param string $field The column: date/domain
     * @param string $orderby Orderby type: ASC/DESC
     */
    public function setOrderby($orderby, $field = "date")
    {
        $this->request =  preg_replace('/(#ORDERBY#)/i', 'ORDER BY '.$field.' '.$orderby, $this->request);
    }

    /**
     * Set the limit of items for a page.
     *
     * @param int $integer The items limit for a page
     */
    public function setLimit($integer)
    {
        $this->limit = $integer;
    }

    /**
     * Returns the cleared select request.
     *
     * @return request $request The select request
     */
    private function getClearedRequest()
    {
        $request = preg_replace('/(#(\w+)#)/i', "", $this->request);
        if (!preg_match('/LIMIT/i', $request)) {
            $request.=" LIMIT 1000";
        }
        return $request;
    }

    /**
     * Returns the total number of items.
     *
     * @return int Total number of items
     */
    public function total()
    {
        $request = $this->getClearedRequest();
        $request =  preg_replace('/(LIMIT.*)/i', "", $request);
        $results = $this->instance->query(preg_replace('/(\*)/i', "count(*)", $request));
        $row = $results->fetch();
        $nb_results = $row["count(*)"];
        return $nb_results;
    }

    /**
     * Returns the total number of pages.
     *
     *  @return int Total number of pages
     */
    public function pages()
    {
        $request = $this->getClearedRequest();
        $request =  preg_replace('/(LIMIT.*)/i', "", $request);
        $results = $this->instance->query(preg_replace('/(\*)/i', "count(*)", $request));
        $row = $results->fetch();
        $nb_results = $row["count(*)"];
        return ceil($nb_results/$this->limit);
    }

    /**
     * Returns the list of items for a given page.
     *
     * @param int $page The page number
     * @return list The list of items
     */
    public function getList($page)
    {
        try {
            $this->instance->query("SELECT 1 FROM backorder_pending_domains LIMIT 1");
        } catch (Exception $e) {
            die($e->getMessage());
        }
        $list = array();
        $start = ($page-1) * $this->limit;
        $this->request =  preg_replace('/(#LIMIT#)/i', "LIMIT ".$start.", ".$this->limit, $this->request);
        $results = $this->instance->query($this->getClearedRequest());
        while ($row = $results->fetch()) {
            $list[] = $row;
        }
        return $list;
    }

    /**
     * Returns the list of items for a given first.
     *
     * @param int $page The first item
     * @return list The list of items
     */
    public function getListByFirst($first)
    {
        try {
            $this->instance->query("SELECT 1 FROM backorder_pending_domains LIMIT 1");
        } catch (Exception $e) {
            die($e->getMessage());
        }
        $list = array();
        $start = $first;
        $this->request =  preg_replace('/(#LIMIT#)/i', "LIMIT ".$start.", ".$this->limit, $this->request);
        $results = $this->instance->query($this->getClearedRequest());
        while ($row = $results->fetch()) {
            $list[] = $row;
        }
        return $list;
    }

    /**
     * Display the paging bar.
     *
     * @param int $currentPage The current page
     * @param int $nb The number of pages displayed
     */
    public function displayPaging($currentPage, $nb = 10)
    {
        $from = $currentPage-(($nb/2)-1);
        $to = $currentPage+($nb/2);
        $pages = $this->pages();

        if ($from <= 0) {
            $from = 1;
            if ($pages>$nb) {
                $to = $nb;
            } else {
                $to = $pages;
            }
        }
        if ($to > $pages) {
            $to = $pages;
            if ($pages > $nb) {
                $from = $pages - ($nb-1);
            } else {
                $from = 1;
            }
        }

        $first = 1;
        $last = $pages;
        if ($currentPage>1) {
            $prev=$currentPage-1;
        }
        if ($currentPage<$pages) {
            $next=$currentPage+1;
        }

        $string = '<div class="paging">';
        if ($currentPage != $first) {
            $string .= '<a class="first" href="'.$_SERVER['PHP_SELF'].'?page='.$first.'">First</a> | ';
        }
        if (isset($prev)) {
            $string .= '<a class="prev" href="'.$_SERVER['PHP_SELF'].'?page='.$prev.'">Prev</a> | ';
        }

        for ($i=$from; $i <= $to; $i++) {
            if ($i == $_GET["page"]) {
                $string .= '<a class="current" href="'.$_SERVER['PHP_SELF'].'?page='.$i.'">'.$i.'</a> | ';
            } else {
                $string .= '<a href="'.$_SERVER['PHP_SELF'].'?page='.$i.'">'.$i.'</a> | ';
            }
        }
        if (isset($next)) {
            $string .= '<a class="next" href="'.$_SERVER['PHP_SELF'].'?page='.$next.'">Next</a> | ';
        }
        if (($currentPage != $last) && ($last != 0)) {
            $string .= '<a class="last" href="'.$_SERVER['PHP_SELF'].'?page='.$last.'">Last</a>';
        }
        $string .= '</div>';
        echo $string;
    }
}
