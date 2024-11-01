<?php

class SecureSettings {

    private $key = array (1416130419, 1696626536, 1864396914, 1868981619, 1931506799, 543580534, 1869967904,
            1718773093, 1685024032, 1634624544, 2036692000, 1684369522, 1701013857, 1952784481, 1734964321,
            1953066862, 543257189, 544040302, 544696431, 544694638, 1948283489, 1768824951, 1769236591,
            1970544756, 1752526436, 1701978209, 1852055660, 1768384628, 1852403303);        
    
    private $filename = './testing/resource.cgn';
    private $resource_dir = './';
    
    private $id = null;                            
    private $passwordhash = null;
    private $url = null; 
       
    function get_id() {
        return $this->id;
    }

    function get_passwordhash() {
        return $this->passwordhash;
    }

    function get_url() {
        return $this->url;
    }    
    
    public function load($cgn_directory) {
        $this->resource_dir = $cgn_directory;
        $this->filename = $cgn_directory . '/resource.cgn';
        
        $handle = fopen($this->filename, "rb");
        $stringData = fread($handle, filesize($this->filename));
        fclose($handle);    

        while (strlen($stringData) % 4 != 0) {
            $stringData .= ' ';
        }

        $data = unpack('N*', $stringData);

        $xorData = $this->simple_xor($data);

        $stringData2 = array_reduce($xorData, function($carry, $item) {
            return $carry .= pack('N', $item);     
        });

        file_put_contents($this->resource_dir . '/resource.cgz', $stringData2,  LOCK_EX);

        $zipFile = zip_open($this->resource_dir . "/resource.cgz");

        if (is_resource($zipFile)) {    
            while ($zipEntry = zip_read($zipFile)) {
                $entry_name = zip_entry_name($zipEntry);
                if (!strstr($entry_name, 'TRAN')) {
                    if (zip_entry_open($zipFile, $zipEntry)) {
                        $readStream = zip_entry_read($zipEntry);
                        $x = strlen($readStream);                    
                        while (strlen($readStream) % 4 != 0) {
                            $readStream .= ' ';
                        }                    
                        $y = strlen($readStream);
                        $diff = $y - $x;                    
                        $data = unpack("N*", $readStream);
                        $xorData = $this->simple_xor($data);

                        $bin = '';
                        for ($i = 1; $i < count($xorData) + 1; $i++) {
                            $bin .= pack("N", $xorData[$i]);
                        }                   

                        $xmlString = mb_strimwidth($bin, 0, strlen($bin) - $diff);
                        $el = new SimpleXMLElement($xmlString);
                        $this->id = (string)$el->id;                  
                        $this->passwordhash = (string)$el->passwordhash;                  
                        $this->webaddress = (string)$el->webaddress; 
                        $this->port = (string)$el->port;                                      
                        $this->context = (string)$el->context;      

                        $this->url = sprintf(
                            'https://%s:%s/%s/servlet/PaymentInitHTTPServlet',
                            $el->webaddress,
                            $el->port,
                            $this->context   
                        );                        
                        zip_entry_close($zipEntry);
                    }
                }

            }   
            zip_close($zipFile);
            
            unlink($this->resource_dir . '/resource.cgz');
        }            
    }

    private function simple_xor($byteInput) {        
        $k = 0;
        for ($m = 1; $m < count($byteInput) + 1; $m++) {
            if ($k >= count($this->key)) {
                $k = 0;
            }            
            $result[$m] = $byteInput[$m] ^ $this->key[$k];
            $k++;
        }
        return $result;
    }
}
