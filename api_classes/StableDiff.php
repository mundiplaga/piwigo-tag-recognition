<?php 

class StableDiff extends API {

    function getInfo() : array
    {
        return [
            "icon" => 'https://imagga.com/static/images/logo.svg',
            "site" => 'https://example.com',
            "info" => `
            Stable Diffusion Interrogation
            `
        ];
    }

    function getConfParams() : array
    {
        return [
            'USER' => 'API Key', 
            'USER_PASSWORD'=> 'API Secret'
        ];
    }

    function generateTags($conf, $params) : array
    {
        $file_path = $this->getFileName($params['imageId']);

        if (!(isset($conf['USER']) && isset($conf['USER_PASSWORD'])))
            throw new Exception('API parameters are not set');

        $api_credentials = array(
            'key' => $conf['USER'],
            'secret' => $conf['USER_PASSWORD']
        );

        $type = pathinfo($file_path, PATHINFO_EXTENSION);
        $data = file_get_contents($file_path);
        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://136.29.101.154:7861/sdapi/v1/interrogate");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        // curl_setopt($ch, CURLOPT_USERPWD, $api_credentials['key'].':'.$api_credentials['secret']);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, 1);
        $data = [
            'image' => $base64,
            'model' => 'deepdanbooru',
        ];
        $json_data = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        
        
        if (curl_errno($ch)) 
        {
            return [curl_error($ch)];
        }
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $json_response = json_decode($response);
        if ($json_response === null) {
            // JSON decoding failed, handle the error
            $jsonError = json_last_error();
            $jsonErrorMsg = json_last_error_msg();
            error_log("JSON decoding error (code: $jsonError): $jsonErrorMsg");
        } else {
            if (isset($json_response->caption)) {
                $caption = $json_response->caption;
                $tags = explode(',', $caption);
                $limit_tags = array_slice($tags, 0, $params['limit']);
            } else {
                error_log("The 'caption' property is missing in the JSON response.");
            }
            return $limit_tags;
        }
    }
}
