<?php

namespace HEXONET;

// the DB File
const DB_FILE = "dropcatching.json";

//The URL of the PendingDeleteList file
const PENDING_DELETE_LIST_FILE_URL = "https://www.hexonet.net/files/domain-backordering/pending_delete_domain_list.csv.zip";

/**
 * PendingDomainList
 *
 * The PHP implementation of the pending domain list class
 *
 * @author Hexonet GmbH
 *
 */
class PendingDomainList
{
    public function __construct()
    {
        set_time_limit(0);
    }

    /**
     * Download the PendingDeleteList file on Hexonet's website in the current working directory.
     */
    public function download()
    {
        $path = $this->getPath();        
        $tmpfile = $path . "pending_delete_list_tmp.zip";
        $fp = fopen($tmpfile, 'w+');

        if ($fp === FALSE) {
            die("Unable to create file $tmpfile");
       }


       $ch = curl_init(PENDING_DELETE_LIST_FILE_URL);
       curl_setopt_array($ch, [
           CURLOPT_TIMEOUT => 50,
           CURLOPT_FOLLOWLOCATION => true,
           CURLOPT_RETURNTRANSFER => true, //IMPORTANT in order to avoid displaying content in the browser.
           CURLOPT_FILE => $fp
       ]);

       $data = curl_exec($ch);
       curl_close($ch);
       fclose($fp);

       if ($data === FALSE) {
           die("Unable to download file " . PENDING_DELETE_LIST_FILE_URL . " via curl");
       }
       return $this;
    }

    /**
     * Import the Pending Delete List using the ZIP archive.
     */
    public function import()
    {
        $path = $this->getPath();
        
        if (function_exists("zip_open")) {
            $zip = zip_open($path . "pending_delete_list_tmp.zip");
            //ref: https://www.php.net/manual/en/ref.zip.php
            while ($zip_entry = zip_read($zip)) {
                //open the entry
                if (zip_entry_open($zip, $zip_entry, "r")) {
                    //the name of the file to save on the disk
                    $file_name = $path . zip_entry_name($zip_entry);
                    //get the content of the zip entry
                    $fstream = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
                    file_put_contents($file_name, $fstream);
                    //set the rights
                    chmod($file_name, 0777);
                    //close the entry
                    zip_entry_close($zip_entry);
                }
            }
            //close the zip-file
            zip_close($zip);

            $handle = fopen($path . "pending_delete_domain_list.csv", "r");
        } else {
            $handle = fopen("zip://" . $path . "pending_delete_list_tmp.zip#pending_delete_domain_list.csv", "r");
        }

        if ($handle === false) {
            die("Unable to open csv file.");
        }

        file_put_contents(DB_FILE, "["); // this replaces the file
        while (($data = fgetcsv($handle, 1000, ";")) !== false) {
            // skip wrong domain names
            if (!(bool)preg_match("/^([^.]*)\.(.*)$/", strtolower($data[0]), $m)) {
                continue;
            }
            // skip outdated entries
            $dropdate = strtotime(str_replace(" ", "T", $data[1]) . "Z");
            if (strtotime("now") > $dropdate) {
                continue;
            }

            // build up json list
            list($domain, $zone) = $m;
            $dnDigits = preg_match_all("/[0-9]/", $domain);            
            $row = [
                "domain" => $domain,
                "zone"  => $zone,
                "dropdate" => $dropdate,
                "dnLength" => strlen($domain),
                "dnHyphens" => substr_count($domain, '-'),
                "dnDigits" => $dnDigits === false ? 0 : $dnDigits
            ];
            file_put_contents(DB_FILE, json_encode($row), FILE_APPEND | LOCK_EX);
            //echo date("Y-m-d H:i:s ") . implode(" | ", array_values($row)) . "\n";
        }
        file_put_contents(DB_FILE, "]", FILE_APPEND | LOCK_EX);

        fclose($handle);
        
        return $this;
    }

    private function SyncDropDate() {
        if ($local["dropdate"] != $online["drop_date"] && $online["drop_date"] > date("Y-m-d H:i:s")) {
            $old_dropdate = $local["dropdate"];
            $new_dropdate = $online["drop_date"];

            $update_stmt = $pdo->prepare("UPDATE backorder_domains SET dropdate=?, updateddate=NOW() WHERE domain=? AND tld=?");
            $update_stmt->execute(array($online["drop_date"], $local["domain"], $local["tld"]));
            if ($update_stmt->rowCount() != 0) {
                $message = "DROPDATE OF BACKORDER " . $local["domain"] . "." . $local["tld"] . " (backorderid=" . $local["id"] . ") SYNCHRONIZED ($old_dropdate => $new_dropdate)";
                logmessage($cronname, "ok", $message);
            }
        }
    }

    private function getPath() {
        if (!isset($GLOBALS["downloads_dir"])) {
            die("Cannot find tmp directory!");
        }
        $path = $GLOBALS["downloads_dir"];
        if (substr($path, -1) !== DIRECTORY_SEPARATOR) { // installation not secured
            $path .= DIRECTORY_SEPARATOR;
        }
        return $path;
    }
}
