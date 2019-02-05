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

/**
 * The common model implemented by Google Datastore, mysql, etc.
 */
interface DataModelInterface
{
    /**
     * Lists all the locations in the data model.
     * Cannot simply be called 'list' due to PHP keyword collision.
     *
     * @param int  $limit  How many locations will we fetch at most?
     * @param null $cursor Returned by an earlier call to listLocations().
     *
     * @return array ['locations' => array of associative arrays mapping column
     *               name to column value,
     *               'cursor' => pass to next call to listLocations() to fetch
     *               more locations]
     */
    public function listLocations($limit = 1000, $cursor = null);

    /**
     * Creates a new location in the data model.
     *
     * @param $lovation array  An associative array.
     * @param null $id integer  The id, if known.
     *
     * @return mixed The id of the new location.
     */
    public function createLocation($location, $id = null);

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
    public function listPhotolocations($atLocation, $limit = 1000, $cursor = null);
    
    /**
     * Reads a book from the data model.
     *
     * @param $id  The id of the book to read.
     *
     * @return mixed An associative array representing the book if found.
     *               Otherwise, a false value.
     */
    public function readLocation($location);

    /**
     * Updates a book in the data model.
     *
     * @param $book array  An associative array representing the book.
     * @param null $id The old id of the book.
     *
     * @return int The number of books updated.
     */
    public function updateLocation($location);

    /**
     * Deletes a book from the data model.
     *
     * @param $id  The book id.
     *
     * @return int The number of books deleted.
     */
    public function deleteLocation($location);
}
