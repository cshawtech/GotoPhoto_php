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

namespace GotoPhoto;

/*
 * Adds all the controllers to $app.  Follows Silex Skeleton pattern.
 */
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
//use GotoPhoto\DataModel\DataModelInterface;

$app->get('/', function (Request $request) use ($app) {
    return $app->redirect('/locations/');
});

// [START index]
$app->get('/locations/', function (Request $request) use ($app) {
    syslog(LOG_DEBUG, "Locations endpoint");
    /** @var DataModelInterface $model */
    $model = $app['gotophoto.model'];
    /** @var Twig_Environment $twig */
    syslog(LOG_DEBUG, "Loading twig");
    $twig = $app['twig'];
    syslog(LOG_DEBUG, "Finding request token");
    $token = $request->query->get('page_token');
    syslog(LOG_DEBUG, "Requesting location list");
    $locationList = $model->listLocations($app['gotophoto.page_size'], $token);
    syslog(LOG_DEBUG, "Rendering page");

    return $twig->render('list.html.twig', array(
        'locations' => $locationList['locations'],
        'next_page_token' => $locationList['cursor'],
    ));
});
// [END index]

// [START json_locations]
$app->get('/v1/locations/', function (Request $request) use ($app) {
    /** @var DataModelInterface $model */
    $model = $app['gotophoto.model'];
    /** @var Twig_Environment $twig */
    $twig = $app['twig'];
    $token = $request->query->get('page_token');
    $locationList = $model->listLocations($app['gotophoto.page_size'], $token);

    /*
    return $twig->render('list.html.twig', array(
        'locations' => $locationList['locations'],
        'next_page_token' => $locationList['cursor'],
    ));
     * */
    return json_encode($locationList['locations'], JSON_NUMERIC_CHECK);
     
});
// [END json_locations]

// [START photolocations]
$app->get('/photolocations/', function (Request $request) use ($app) {
    /** @var DataModelInterface $model */
    $model = $app['gotophoto.model'];
    /** @var Twig_Environment $twig */
    $twig = $app['twig'];
    $token = $request->query->get('page_token');
    $photolocationList = $model->listPhotoLocations($app['gotophoto.page_size'], $token);

    return $twig->render('list.html.twig', array(
        'photolocations' => $photolocationList['photolocations'],
        'next_page_token' => $photolocationList['cursor'],
    ));
});
// [END photolocations]

// [START add]
$app->get('/locations/add', function () use ($app) {
    /** @var Twig_Environment $twig */
    $twig = $app['twig'];

    return $twig->render('form.html.twig', array(
        'action' => 'Add',
        'location' => array(),
    ));
});

$app->post('/locations/add', function (Request $request) use ($app) {
    /** @var DataModelInterface $model */
    $model = $app['gotophoto.model'];
    $location = $request->request->all();
    $id = $model->create($location);

    return $app->redirect("/locations/$id");
});
// [END add]

// [START show]
$app->get('/locations/{id}', function ($id) use ($app) {
    /** @var DataModelInterface $model */
    $model = $app['gotophoto.model'];
    $location = $model->readLocation($id);
    if (!$location) {
        return new Response('', Response::HTTP_NOT_FOUND);
    }
    /** @var Twig_Environment $twig */
    $twig = $app['twig'];

    return $twig->render('view.html.twig', array('location' => $location));
});
// [END show]

// [START json]
$app->get('/v1/locationphotos/{locid}', function ($locid) use ($app) {
    /** @var DataModelInterface $model */
    $model = $app['gotophoto.model'];
    $photos = $model->listPhotolocations($locid);
    if (!$photos) {
        return new Response('', Response::HTTP_NOT_FOUND);
    }
    /** @var Twig_Environment $twig */
    //$twig = $app['twig'];

    //return $twig->render('view.html.twig', array('location' => $location));
    
    return json_encode($photos['photolocations'], JSON_NUMERIC_CHECK);
});
// [END json]

// [START edit]
$app->get('/locations/{id}/edit', function ($id) use ($app) {
    /** @var DataModelInterface $model */
    $model = $app['gotophoto.model'];
    $location = $model->read($id);
    if (!$location) {
        return new Response('', Response::HTTP_NOT_FOUND);
    }
    /** @var Twig_Environment $twig */
    $twig = $app['twig'];

    return $twig->render('form.html.twig', array(
        'action' => 'Edit',
        'location' => $location,
    ));
});

$app->post('/locations/{id}/edit', function (Request $request, $id) use ($app) {
    $location = $request->request->all();
    $location['id'] = $id;
    /** @var DataModelInterface $model */
    $model = $app['gotophoto.model'];
    if (!$model->read($id)) {
        return new Response('', Response::HTTP_NOT_FOUND);
    }
    if ($model->update($location)) {
        return $app->redirect("/location/$id");
    }

    return new Response('Could not update location');
});
// [END edit]

// [START delete]
$app->post('/locations/{id}/delete', function ($id) use ($app) {
    /** @var DataModelInterface $model */
    $model = $app['gotophoto.model'];
    $location = $model->read($id);
    if ($location) {
        $model->delete($id);

        return $app->redirect('/locations/', Response::HTTP_SEE_OTHER);
    }

    return new Response('', Response::HTTP_NOT_FOUND);
});
// [END delete]
