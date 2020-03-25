<?php
namespace BxdFramework\RestCall;

class RestAPI
{

    /**
     * @param mixed $url        REST服务器端网址
     * @param mixed $method     方法
     * @param array $params     参数
     * @param mixed $request    请求方式(GET, POST, PUT, DELETE)
     */
    static public function restCall($url, $params, $request)
    {
        $posts = '';
        if (!empty($params)) {
            $posts = http_build_query($params);
        }

        if($request != 'POST' && $request != 'PUT' && $request != 'DELETE') $request = 'GET';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 300);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

        $headers[] = 'Accept: application/json';
        switch($request) {
            case 'GET':
                $url .= '?' . $posts;
                break;

            case 'POST':
                curl_setopt($curl, CURLOPT_POST, TRUE);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $posts);
                break;

            case 'PUT':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($curl, CURLOPT_POSTFIELDS, $posts);
                $headers[] = 'X-HTTP-Method-Override: PUT';
                break;

            case 'DELETE':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                curl_setopt($curl, CURLOPT_POSTFIELDS, $posts);
                break;
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $data = curl_exec($curl); // 执行预定义的CURL
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE); // 获取http返回值
        
        curl_close($curl);
        $res = json_decode($data, true);
        return ['status' => $status, 'result' => $res];
    }
}
