<?php

use Slim\Http\Request;
use Slim\Http\Response;
// use Slim\Views\Twig as View;
// Routes

// $app->get('/[{name}]', function (Request $request, Response $response) {
//     // Sample log message
//     $this->logger->info("Slim-Skeleton '/' route");
//     // Render index view
//     $args=[];
//     return $this->view->render($response, 'index.twig',$args);
// });

$app->get('/register', function(Request $request, Response $response, $args) {
    return $this->view->render($response, 'register.twig', [
        'message' => '',
        'form' => [
            'login' => ''
        ]
    ]);
})->setName('register');


$app->post('/register', function(Request $request, Response $response, $args) {
    $tplVars = [
        'message' => '',
        'form' => [
            'login' => ''
        ]
    ];
    $input = $request->getParsedBody();
    if(!empty($input['login'] && !empty($input['pass1']) && !empty($input['pass2']))) {
        if($input['pass1'] == $input['pass2']) {
            try {
                //prepare hash
                $pass = password_hash($input['pass1'], PASSWORD_DEFAULT);
                //insert data into database
                $stmt = $this->db->prepare('INSERT INTO account (login, password) VALUES (:l, :p)');

                $stmt->bindValue(':l', $input['login']);
                $stmt->bindValue(':p', $pass);
                $stmt->execute();
                //redirect to login page
                return $response->withHeader('Location', $this->router->pathFor('login'));
                exit;
            } catch (PDOException $e) {
                $this->logger->error($e->getMessage());
                $tplVars['message'] = 'Database error.';
                $tplVars['form'] = var_dump($input);
            }
        } else {
            $tplVars['message'] = 'Provided passwords do not match.';
            $tplVars['form'] = $input;
        }
    }
    return $this->view->render($response, 'register.twig', $tplVars);
})->setName('do-register');


$app->get('/login', function(Request $request, Response $response, $args) {
    return $this->view->render($response, 'login.twig', ['message' => '']);
})->setName('login');


$app->post('/login', function(Request $request, Response $response, $args) {
    try {
    	$input = $request->getParsedBody();
        //retrieve login and password from request
        $login = $input['login'];
        $pass = $input['pass'];
        //find user by login
        $stmt = $this->db->prepare('SELECT * FROM account WHERE login = :l');
        $stmt->bindValue(':l', $login);
        $stmt->execute();
        $user = $stmt->fetch();
        if ($user) {
            //verify if hash from database matches hash of provided password
            echo var_dump($user);
            if (password_verify($pass, $user["password"])) {
                // echo "USER VERIFIED";
                $_SESSION["user"] = $user;
                 return $response->withHeader('Location', $this->router->pathFor('profile'));
            }
        }
        //do not reveal if account exists or not
        $tplVars['message'] = "User verification failed.";
        return $this->view->render($response, 'login.twig', $tplVars);
    } catch (PDOException $e) {
        $tplVars['message'] = $e->getMessage();
    }
})->setName('do-login');

$app->group('/auth', function() use($app) {
	
	$app->get('/profile', function(Request $request, Response $response, $args) {
	    return  $this->view->render($response, 'profile.twig', ['user' => $_SESSION['user'] ]);
	})->setName('profile');

	$app->post('/logout', function(Request $request, Response $response, $args) {
        session_destroy();
        return $response->withHeader('Location', $this->router->pathFor('login'));
    })->setName('logout');



})->add(function($request, $response, $next) {
    if(!empty($_SESSION['user'])) {
        return $next($request, $response);
    } else {
        return $response->withHeader('Location', $this->router->pathFor('login'));
    }
});
