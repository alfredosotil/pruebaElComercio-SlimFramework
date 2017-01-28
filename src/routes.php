<?php

// Routes

$app->get('/', function ($request, $response, $args) {
    $this->logger->info("List Users '/' route");
    $args['uri'] = $request->getUri()->withPath($this->router->pathFor('view'));
    $args['uriSearch'] = $request->getUri()->withPath($this->router->pathFor('search'));
    $args['userList'] = json_decode(file_get_contents('../data/employees.json'), TRUE);
    return $this->renderer->render($response, 'index.phtml', $args);
})->setName('index');
$app->get('/view/[{id}]', function ($request, $response, $args) {
    $this->logger->info("View User'/view' route");
    $args['uri'] = $request->getUri()->withPath($this->router->pathFor('index'));
    $userList = json_decode(file_get_contents('../data/employees.json'), TRUE);
    foreach ($userList as $user) {
        if ($args['id'] === $user['id']) {
            $args['userInfo'] = $user;
            break;
        }
    }
    return $this->renderer->render($response, 'view.phtml', $args);
})->setName('view');
$app->get('/search/[{email}]', function ($request, $response, $args) {
    $this->logger->info("Search user'/search' route");
    $obj = new stdClass();
    $obj->items = array();
    $userList = json_decode(file_get_contents('../data/employees.json'), TRUE);
    foreach ($userList as $user) {
        if (!(strpos($user['email'], $args['email']) === false)) {
            $user['html_url'] = $request->getUri()->withPath($this->router->pathFor('view')) . $user['id'];
            array_push($obj->items, $user);
        }
    }
    $obj->count = count($obj->items);
    $newResponse = $response->withJson($obj, 200, null);
    return $newResponse;
})->setName('search');
$app->get('/searchrangesalary/[{min},{max}]', function ($request, $response, $args) {
    $this->logger->info("Search range salary'/searchrangesalary' route");
    $obj = array();
    $userList = json_decode(file_get_contents('../data/employees.json'), TRUE);
    foreach ($userList as $user) {
        $salary = intval(str_replace(array('$', ','), '', $user['salary']));
        if ($salary > intval($args['min']) && $salary < intval($args['max'])) {
            $user['html_url'] = $request->getUri()->withPath($this->router->pathFor('view')) . $user['id'];
            array_push($obj, $user);
        }
    }
//    header('Content-type: text/xml');
    header("Content-type: application/xml; charset=utf-8");
//    $response2 = $response->withHeader('Content-type', 'text/xml');
    $xml = array2xml($obj, false);
//    array_walk_recursive($obj->items, array($xml, 'addChild'));
//    return $response2->getBody()->write($xml);
    echo htmlspecialchars($xml);
//    return $response2->getBody()->write(htmlspecialchars($xml));
})->setName('searchrangesalary');

function array2xml($array, $xml = false) {
    if ($xml === false) {
        $xml = new SimpleXMLElement('<result/>');
    }
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            array2xml($value, $xml->addChild($key));
        } else {
            $xml->addChild($key, $value);
        }
    }
    return $xml->asXML();
}
