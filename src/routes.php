<?php

// Routes
//lista de usuarios
$app->get('/', function ($request, $response, $args) {
    $this->logger->info("List Users '/' route");
    $args['uri'] = $request->getUri()->withPath($this->router->pathFor('view'));
    $args['uriSearch'] = $request->getUri()->withPath($this->router->pathFor('search'));
    $args['userList'] = json_decode(file_get_contents('../data/employees.json'), TRUE);
    return $this->renderer->render($response, 'index.phtml', $args);
})->setName('index');
//vista de usuario segun el id
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
//buscar usuario por email
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
//buscar usuario por rango de salario 
$app->get('/searchrangesalary/[{min},{max}]', function ($request, $response, $args) {
    $this->logger->info("Search range salary'/searchrangesalary' route");
    $obj = array();
    $userList = json_decode(file_get_contents('../data/employees.json'), TRUE);
    foreach ($userList as $user) {
        $salary = intval(str_replace(array('$', ','), '', $user['salary']));
        if ($salary > intval($args['min']) && $salary < intval($args['max'])) {
            array_push($obj, $user);
        }
    }
    $xml_user_info = new SimpleXMLElement("<?xml version=\"1.0\"?><users></users>");
    array_to_xml($obj, $xml_user_info);
    return $response->withStatus(200)
                    ->withHeader('Content-type', 'Content-type: text/xml; charset=utf-8')
                    ->write($xml_user_info->asXML());
})->setName('searchrangesalary');
//function adicional para convertir de array to xml
function array_to_xml($array, &$xml_user_info) {
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            if (!is_numeric($key)) {
                $subnode = $xml_user_info->addChild("$key");
                array_to_xml($value, $subnode);
            } else {
                $subnode = $xml_user_info->addChild("item$key");
                array_to_xml($value, $subnode);
            }
        } else {
            $xml_user_info->addChild("$key", htmlspecialchars("$value"));
        }
    }
}
