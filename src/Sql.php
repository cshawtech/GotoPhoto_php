<?php
/*
 * Copyright 2015 Google Inc. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

//namespace GotoPhoto\DataModel;

//use PDO;
require_once __DIR__ . '/DataModelInterface.php';

/**
 * Class Sql implements the DataModelInterface with a mysql or postgres database.
 *
 */
class Sql implements DataModelInterface
{
    private $dsn;
    private $user;
    private $password;

    /**
     * Creates the SQL locations and photolocations tables if they don't already exist.
     */
    public function __construct($dsn, $user, $password)
    {
        $this->dsn = $dsn;
        $this->user = $user;
        $this->password = $password;

        $location_columns = array(
            'id serial PRIMARY KEY ',
            'title VARCHAR(255)'
            );
        
        $photolocation_columns = array(
            'id serial PRIMARY KEY ',
            'location INT',
            'title VARCHAR(255)',
            'latitude DOUBLE',
            'longitude DOUBLE',
            'image_url VARCHAR(255)',
            'description VARCHAR(255)'
        );

        $this->locationColumnNames = array_map(function ($columnDefinition) {
            return explode(' ', $columnDefinition)[0];
        }, $location_columns);
        $this->photolocationColumnNames = array_map(function ($columnDefinition) {
            return explode(' ', $columnDefinition)[0];
        }, $photolocation_columns);
        $locationColumnText = implode(', ', $location_columns);
        $photolocationColumnText = implode(', ', $photolocation_columns);
        syslog(LOG_DEBUG, "Creating SQL connection");
        $pdo = $this->newConnection();
        syslog(LOG_DEBUG, "SQL connection completed");
        $pdo->query("CREATE TABLE IF NOT EXISTS locations ($locationColumnText)");
        $pdo->query("CREATE TABLE IF NOT EXISTS photolocations ($photolocationColumnText)");
        syslog(LOG_DEBUG, "SQL created");

    }

    /**
     * Creates a new PDO instance and sets error mode to exception.
     *
     * @return PDO
     */
    private function newConnection()
    {
        syslog(LOG_DEBUG, "New PDO: " . $this->dsn . ", " . $this->user . ", " . $this->password);
        $pdo = new PDO($this->dsn, $this->user, $this->password);
        syslog(LOG_DEBUG, "Connected. Setting attribute");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }

    /**
     * Throws an exception if $location contains an invalid key.
     *
     * @param $location array
     *
     * @throws \Exception
     */
    private function verifyLocation($location)
    {
        if ($invalid = array_diff_key($location, array_flip($this->columnNames))) {
            throw new \Exception(sprintf(
                'unsupported location properties: "%s"',
                implode(', ', $invalid)
            ));
        }
    }

    public function listLocations($limit = 1000, $cursor = null)
    {
        $pdo = $this->newConnection();
        if ($cursor) {
            $query = 'SELECT * FROM locations WHERE id > :cursor ORDER BY id' .
                ' LIMIT :limit';
            $statement = $pdo->prepare($query);
            $statement->bindValue(':cursor', $cursor, PDO::PARAM_INT);
        } else {
            $query = 'SELECT * FROM locations ORDER BY id LIMIT :limit';
            $statement = $pdo->prepare($query);
        }
        $statement->bindValue(':limit', $limit + 1, PDO::PARAM_INT);
        $statement->execute();
        $rows = array();
        $last_row = null;
        $new_cursor = null;
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            if (count($rows) == $limit) {
                $new_cursor = $last_row['id'];
                break;
            }
            array_push($rows, $row);
            $last_row = $row;
        }

        return array(
            'locations' => $rows,
            'cursor' => $new_cursor,
        );
    }
    
        /**
     * Lists all the photos at one location in the data model.
     * Cannot simply be called 'list' due to PHP keyword collision.
     *
     * @param int $atLocation ID of location to query
     * @param int  $limit  How many photolocations will we fetch at most?
     * @param null $cursor Returned by an earlier call to listPhotolocations().
     *
     * @return array ['photolocations' => array of associative arrays mapping column
     *               name to column value,
     *               'cursor' => pass to next call to listPhotolocations() to fetch
     *               more photolocations]
     */
    public function listPhotolocations($atLocation, $limit = 1000, $cursor = null)
    {
        $pdo = $this->newConnection();
        if ($cursor) {
            $query = 'SELECT * FROM photolocations WHERE location = :location AND id > :cursor ORDER BY id' .
                ' LIMIT :limit';
            $statement = $pdo->prepare($query);
            $statement->bindValue(':cursor', $cursor, PDO::PARAM_INT);
        } else {
            $query = 'SELECT * FROM photolocations WHERE location = :location ORDER BY id LIMIT :limit';
            $statement = $pdo->prepare($query);
        }
        $statement->bindValue(':location', $atLocation, PDO::PARAM_INT);
        $statement->bindValue(':limit', $limit + 1, PDO::PARAM_INT);
        $statement->execute();
        $rows = array();
        $last_row = null;
        $new_cursor = null;
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            if (count($rows) == $limit) {
                $new_cursor = $last_row['id'];
                break;
            }
            array_push($rows, $row);
            $last_row = $row;
        }

        return array(
            'photolocations' => $rows,
            'cursor' => $new_cursor,
        );
    }

    public function createLocation($location, $id = null)
    {
        $this->verifyLocation($location);
        if ($id) {
            $location['id'] = $id;
        }
        $pdo = $this->newConnection();
        $names = array_keys($location);
        $placeHolders = array_map(function ($key) {
            return ":$key";
        }, $names);
        $sql = sprintf(
            'INSERT INTO locations (%s) VALUES (%s)',
            implode(', ', $names),
            implode(', ', $placeHolders)
        );
        $statement = $pdo->prepare($sql);
        $statement->execute($location);

        return $pdo->lastInsertId();
    }

    public function readLocation($id)
    {
        $pdo = $this->newConnection();
        $statement = $pdo->prepare('SELECT * FROM locations WHERE id = :id');
        $statement->bindValue('id', $id, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    public function updateLocation($location)
    {
        $this->verifyLocation($location);
        $pdo = $this->newConnection();
        $assignments = array_map(
            function ($column) {
                return "$column=:$column";
            },
            $this->columnNames
        );
        $assignmentString = implode(',', $assignments);
        $sql = "UPDATE locations SET $assignmentString WHERE id = :id";
        $statement = $pdo->prepare($sql);
        $values = array_merge(
            array_fill_keys($this->columnNames, null),
            $location
        );
        return $statement->execute($values);
    }

    public function deleteLocation($id)
    {
        $pdo = $this->newConnection();
        $statement = $pdo->prepare('DELETE FROM locations WHERE id = :id');
        $statement->bindValue('id', $id, PDO::PARAM_INT);
        $statement->execute();

        return $statement->rowCount();
    }

    public static function getMysqlDsn($dbName, $port, $connectionName = null)
    {
        syslog(LOG_DEBUG, "Connection name: " . $connectionName);
        if ($connectionName) {
            return sprintf('mysql:unix_socket=/cloudsql/%s;dbname=%s',
                $connectionName,
                $dbName);
        }

        return sprintf('mysql:host=35.189.54.98;port=%s;dbname=%s', $port, $dbName);
    }

    public static function getPostgresDsn($dbName, $port, $connectionName = null)
    {
        if ($connectionName) {
            return sprintf('pgsql:host=/cloudsql/%s;dbname=%s',
                $connectionName,
                $dbName);
        }

        return sprintf('pgsql:host=127.0.0.1;port=%s;dbname=%s', $port, $dbName);
    }
}
