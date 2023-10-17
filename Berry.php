<?php
class Berry {
    private $routes = [];
    private $bdd;

    private $cookie_name = 'berry_session';

    private $cookie_user = "berry_user";

    private $base_page = '<!DOCTYPE html>
    <html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Berry</title>
        <style>
            :root {
                --background-color-light: #bac2df;
                --text-color-light: #536ed7;
                --background-color-dark: #213479;
                --text-color-dark: #514ba5;
            }
    
            body {
                background-color: var(--background-color-light);
                color: var(--text-color-light);
                text-align: center;
                font-family: Arial, sans-serif;
                margin-top: 150px;
            }
    
            h1 {
                font-size: 24px;
            }
    
            footer {
                background-color: #f5f5f5;
                padding: 10px;
                position: fixed;
                left: 0;
                bottom: 0;
                width: 100%;
                text-align: center;
                font-size: 12px;
                color: #888;
            }
    
            .berry-img {
                width: 150px;
                margin: 0 auto;
            }
    
            .link {
                color: #536ed7;
                text-decoration: underline;
                margin-top: 10px;
            }
    
            @media (prefers-color-scheme: dark) {
                body {
                    background-color: var(--background-color-dark);
                    color: var(--text-color-dark);
                }
                footer {
                    background-color: rgb(52, 52, 52);
                    color: white;
                }
            }
        </style>
    </head>
    <body>
        <h1>Server successfully started, but there are no routes or the "/" route is empty</h1>
        <img class="berry-img" src="https://cdn1.iconfinder.com/data/icons/food-5-7/128/food_Blueberry-Blueberries-Fruit-Berry-2-512.png" alt="Berry">
        <footer>
            Version: 0.2
            <br>
            <a class="link" href="#">Under Testing.</a>
        </footer>
    </body>
    </html>';

    public function route($url, $func) {
        $this->routes[$url] = $func;
    }

    public function serve($ip = '127.0.0.1', $port = '5000') {

        if (!isset($this->routes['/'])) {
            $this->routes['/'] = function() {
                echo $this->base_page;
            };
        }

        if (php_sapi_name() == "cli") {
            $backtrace = debug_backtrace();
            $caller = $backtrace[count($backtrace) - 1];
            $routerScript = basename($caller['file']);
            echo "Starting Berry server on {$ip}:{$port}...\n";
            shell_exec("php -S {$ip}:{$port} -t . {$routerScript}");
        } else {
            $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
            if (file_exists(__DIR__ . $currentPath) && !is_dir(__DIR__ . $currentPath)) {
                $fileInfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $fileInfo->file(__DIR__ . $currentPath);
                header("Content-Type: " . $mimeType);
                readfile(__DIR__ . $currentPath);
                exit;
            }            
    
            if (isset($this->routes[$currentPath])) {
                call_user_func($this->routes[$currentPath]);
                header("x-powered-by: Berry (PHP/8.2.11)");
            } else {
                header("HTTP/1.0 404 Not Found");
                echo "<h1>404 Not Found</h1>";
            }
        }
    }

    public function send_json($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    public function CORS() {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");
    }

    public function render($file) {
        $filePath = __DIR__ . '/' . $file;
        if(file_exists($filePath)) {
            header('Content-Type: text/html');
            include $filePath;
        } else {
            header("HTTP/1.0 500 internal server error");
            echo "<h1>500 internal server error</h1>";
            echo "<p>file $file not found on the server.</p>";
        }
    }
    
    public function redirect($url) {
        header("Location: $url");
        exit; 
    }

    public function connect($server, $database, $user, $passwd) {
        try {
            $this->bdd = new PDO("mysql:host=$server;dbname=$database", $user, $passwd);
            $this->bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->bdd->exec("SET CHARACTER SET utf8");
        } catch (Exception $error) {
            die('Connection error: ' . $error->getMessage());
        }
    }

    public function sql_exec($query, $params = []) {
        if (!$this->bdd) {
            die('Error: DB connection failed.');
        }
        try {
            $stmt = $this->bdd->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (Exception $error) {
            die('SQL error: ' . $error->getMessage());
        }
    }

    public function login($name) {
        $session_value = uniqid('berry_', true);
        setcookie($this->cookie_name, $session_value, time() + 3600, '/');
        setcookie($this->cookie_user, $name, time() + 3600, '/');
        return $session_value;
    }

    public function logout() {
        setcookie($this->cookie_name, '', time() - 3600, '/');
        setcookie($this->cookie_user, '', time() - 3600, '/');
    }

    public function check_login() {
        if(isset($_COOKIE[$this->cookie_name]) && isset($_COOKIE[$this->cookie_user])) {
            return array('status' => true, 'name' => $_COOKIE[$this->cookie_user]);
        }
        return array('status' => false, 'name' => '');
    }
}
?>
