<?php

require_once 'helpers.php';

/**
 * Задание 5 - выполнено за 2 часа 30 минут
 * Реализовать проверку авторизации в restAPI
 * Средствами РНР реализовать два метода restAPI:
 * 1. авторизация
 * На вход метода подается пара логин,пароль: testUser/testPass
 * На выходе ожидается JWT token сроком жизни 10 минут
 * 2. проверка токена
 * На вход метода подается JWT token
 * На выходе ошибка, если токен не прошел проверку.
 *
 * Тестирование
 * Инсталяция апи - ?method=install
 * Авторизация - ?method=auth&user=qwerqwer&pswd=12341234
 * Проверка токена - ?method=getdata&token=%token%
 *
 * Подключаемые модули
 * composer require firebase/php-jwt
 */

ApiHandler::Handle($_GET);


abstract class ApiHandler
{
    private const JWT_KEY = 'E3c799KnB17StWGav&BMzf2qA2BaVQzH';
    private const JWT_ALG = 'HS256';
    private const JWT_TTL = 10 * 60; // sec

    protected array $request;

    /** Сюда писать логику апи при создании новых методов
     * @return mixed
     */
    abstract protected function Work();

    /** Запустить выполнение апи
     * Архитектурная конструкция. НЕ ЛЕЗЬ, УБЬЕТ!
     * @return mixed
     */
    abstract public function Execute();

    public static function Factory(array $request)
    {
        return new static($request);
    }

    public function __construct(array $request)
    {
        $this->request = $request;
    }

    /** Отправить данные клиету и прекратить выполнение скрипта
     * @param $data - данные для отправки
     * @param int $code - HTTP код ответа
     * @throws Exception
     */
    protected static function SendData($data, int $code = 200)
    {
        http_response_code($code);

        if(is_string($data))
        {
            echo $data;
        }
        else if(is_array($data) || is_object($data))
        {
            echo static::EncodeData($data);
        }
        else
        {
            throw new Exception('Data type not supported');
        }

        exit();
    }

    /** Создать JWT
     * @param int $userId
     * @return string - JWT токен
     */
    protected function CreateJWT(int $userId)
    {
        $currTime = time();

        $payload = [
            'userId' => $userId,
            'created' => $currTime,
            'expires' => $currTime + self::JWT_TTL,
        ];

        return \Firebase\JWT\JWT::encode($payload, self::JWT_KEY, self::JWT_ALG);
    }

    /** Декодировать JWT и вернуть userId
     * @param string $jwt - JWT токен
     * @return int - userId или false, в случае неудачи
     */
    protected function CheckJWT(string $jwt)
    {
        try {
            $data = \Firebase\JWT\JWT::decode($jwt, new \Firebase\JWT\Key(self::JWT_KEY, self::JWT_ALG));
        }
        catch(Throwable $ex)
        {
            return false;
        }

        $currTime = time();
        if($data->created <= $currTime && $currTime <= $data->expires)
        {
            return $data->userId;
        }
        else
        {
            return false;
        }
    }

    /** Кодироваать данные для отправки
     * @param $data - данные для кодирования
     * @return false|string
     */
    private static function EncodeData(array $data)
    {
        return json_encode($data);
    }

    /** Точка входа Api
     * @param array $request - данные запроса, например $_GET
     * @throws Exception
     */
    public static function Handle(array $request)
    {
        $method = $request['method'] ?? '';
        unset($request['method']);

        try
        {
            switch($method)
            {
                case 'install':
                {
                    ApiInstallHandler::Factory($request)->Execute();
                    break;
                }
                case 'auth':
                {
                    ApiAuthHandler::Factory($request)->Execute();
                    break;
                }
                case 'getdata':
                {
                    ApiGetDataHandler::Factory($request)->Execute();
                    break;
                }
                default:
                {
                    throw new ApiErrorException('Method not implemented', 501);
                }
            }
        }
        catch(ApiErrorException $ex)
        {
            self::SendData(['error' => $ex->getMessage()], $ex->getCode());
        }
    }
}

/**
 * Class ApiPublicHandler - родительский класс для всех публичных апи методов
 */
abstract class ApiPublicHandler extends ApiHandler
{
    public function Execute()
    {
        $result = $this->Work();
        self::SendData($result);
    }
}

/**
 * Class ApiPrivateHandler - родительский класс для всех апи методов требующих авторизации
 * token - jwt токен
 */
abstract class ApiPrivateHandler extends ApiPublicHandler
{
    public function Execute()
    {
        $jwt = $this->request['token'];
        unset($this->request['token']);

        $userId = $this->CheckJWT($jwt);
        if(!$userId)
        {
            throw new ApiErrorException('Acces token invalid or expired', 401);
        }

        parent::Execute();
    }
}

/**
 * Class ApiInstallHandler - инсталяция апи
 */
class ApiInstallHandler extends ApiPublicHandler
{
    protected function Work()
    {
        $dbh = DBH::Factory();

        $dbh->Query("CREATE TABLE user (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            login VARCHAR(32) NOT NULL,
            pswd VARCHAR(32) NOT NULL
        )");

        $dbh->Query("INSERT INTO user VALUES (null, 'qwerqwer', '12341234')");

        return 'Api successfully installed';
    }
}

/**
 * Class ApiAuthHandler - авторизация пользователя
 * user - логин
 * pswd - пароль
 */
class ApiAuthHandler extends ApiPublicHandler
{
    protected function Work()
    {
        $dbh = DBH::Factory();

        $user = $dbh->Esc($this->request['user'] ?? '');
        $pswd = $dbh->Esc($this->request['pswd'] ?? '');

        $result = $dbh->QueryRow("SELECT id FROM user WHERE login = '$user' AND pswd = '$pswd'");

        if($result)
        {
           $userId = $result['id'];
           $jwt = $this->CreateJWT($userId);
           return ['token' => $jwt];
        }
        else
        {
            throw new ApiErrorException('User not found', 401);
        }
    }
}

/**
 * Class ApiGetDataHandler - получение данных
 */
class ApiGetDataHandler extends ApiPrivateHandler
{
    protected function Work()
    {
        return [
            'test string 1',
            'test string 2',
            'test string 2',
        ];
    }
}

/**
 * Class ApiErrorException - исключение апи
 */
class ApiErrorException extends Exception
{
    public function __construct($message, $code, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
