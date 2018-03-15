pragma solidity ^0.4.21;
/*
  PPk AP based Ethereum Sample 
    PPkPub.org   20180312
  Released under the MIT License.
*/
contract PPkAPoverEthDemo {
    function PPkAPoverEthDemo( ) public {

    }

    //Process PPk AP Interest
    function ppk_ap_interest(string interest_json_or_uri) pure public returns (string) {
        string memory ap_odin_root='ppk:512572.1376';
        string memory ppk_uri;
        
        if(strStartWith(interest_json_or_uri,'{')){
            //todo: parse uri from json encoded string. Now not supported
            return respStatus4XX( ap_odin_root, '403', '403 Forbidden' );
        }else{
            ppk_uri=interest_json_or_uri; 
        }
        
        if(!strStartWith(ppk_uri,ap_odin_root))
            return respStatus4XX( ap_odin_root, '404', 'Not Found' );
        
        uint ap_odin_length = bytes(ap_odin_root).length;
        uint ppk_uri_length = bytes(ppk_uri).length;
        
        string memory uri_resource_path=strSub(ppk_uri,ap_odin_length,ppk_uri_length-ap_odin_length);
        
        if(strEqual(uri_resource_path,'/') || strStartWith(uri_resource_path,'/#'))
            return index( );
        else if(strStartWith(uri_resource_path,'/helloworld(')){
            int argv_start=strPos(uri_resource_path,'(',0)+1;
            int argv_end=strPos(uri_resource_path,')',uint(argv_start));
            return helloworld( strSub( uri_resource_path , uint(argv_start) , uint(argv_end-argv_start) ) );
        }else
            return respStatus4XX( ap_odin_root,'404', 'Not Found' );
    }

    //homepage    
    function index( ) pure public returns (string) {
        return '{"ver":1,"data":{"uri":"ppk:512572.1376\/#1.0","utc":1356789,"status_code":200,"status_info":"OK","metainfo":{"block_id":"1","lastblock_id":"","chunk_index":0,"chunk_count":1,"content_type":"text\/html","content_encrypted":"","content_encoding":"","content_charset":"utf-8","content_length":0,"cache-control":"public"},"content":"<html><head>    <meta charset=\\\"utf-8\\\" \/>    <title>AP Sample based Ethereum<\/title><\/head><body><h1>Peer-Web Demo based PPk AP\/ODIN\/Ethereum<\/h1><p>\u8fd9\u662f\u4e00\u4e2a\u5bf9\u7b49WEB\u539f\u578b\uff0c\u901a\u8fc7PPk AP\/ODIN\u5f00\u653e\u534f\u8bae\u6258\u7ba1\u5728\u4ee5\u592a\u574a\u4e0a,\u5f53\u524d\u8bf7\u6c42\u8bbf\u95ee\u7684PPk URI: ppk:512572.1376\/#1.0<\/p><hr><p><a href=\\\"ppk:512572.1376\/\\\">\u4e3b\u9875<\/a> ppk:512572.1376\/ <\/p><p><a href=\\\"ppk:512572.1376\/helloworld(2018)#\\\">HellowWorld<\/a> ppk:512572.1376\/helloworld(2018)# <\/p><hr><p><img src=\\\"ppk:0\/image\/logo\\\"  title=\\\"0\/image\/logo\\\"> PPkPub Homepage:<a target=\\\"_top\\\" href=\\\"ppk:0\/\\\">ppk:0\/<\/a><\/p><p>TestCharset(UTF-8):  A\u6d4bB\u8bd5C       0 : 41 e6 b5 8b 42 e8 af 95 43 [A...B...C]n<\/body><\/html>","content_length":680},"sign":""}';
    }
    
    //Response 4XX    
    function respStatus4XX( string ap_odin_root,string status_code,string status_detail ) pure public returns (string) {
        string memory str_resp = strConcat('{"ver":1,"data":{"uri":"',ap_odin_root,'\/',status_code,'#1.0","utc":1520830129,"status_code":"');
        str_resp=strConcat(str_resp,status_code,'","status_detail":"',status_detail,'","metainfo":{"block_id":"1","lastblock_id":"","chunk_index":0,"chunk_count":1,"content_type":"text\/html","content_length":0},"content":""},"sign":""}');
        return str_resp;
    }

    //A simple function
    function helloworld(string year) pure public returns (string) {
        return strConcat('{"ver":1,"data":{"uri":"ppk:512572.1376\/helloworld(',year,')#1.0","utc":1520830129,"status_code":200,"status_info":"OK","metainfo":{"block_id":"1","lastblock_id":"","chunk_index":0,"chunk_count":1,"content_type":"text\/html","content_encrypted":"","content_encoding":"","content_charset":"utf-8","content_length":0,"cache-control":"public"},"content":"<html><head>    <meta charset=\\\"utf-8\\\" \/>    <title>Hello world function sample<\/title><\/head><body><h1>Hello world! Welcome ',year,' .<\/h1><\/body><\/html>","content_length":149},"sign":""}');
    }

    function strSub( string _a, uint start, uint length ) pure internal returns (string){
        bytes memory _ba = bytes(_a);

        string memory sub = new string(length);
        bytes memory sub_bytes = bytes(sub);

        for (uint i = 0; i < length && start+i < _ba.length; i++) 
          sub_bytes[i] = _ba[start+i];

        return string(sub_bytes);
    }
    
    function strPos( string memory haystack, string memory needle, uint offset ) pure internal returns (int){
        bytes memory _ba = bytes(haystack);
        bytes memory _bb = bytes(needle);

        for (uint i = offset; i < _ba.length; i++){
            if (_ba[i] == _bb[0]){
                uint k = 1;
                for ( ; k < _bb.length; k++ ) 
                    if ( _ba[i+k] != _bb[k] )
                        break;
                        
                if( k == _bb.length )
                  return int(i);
            }
        } 

        return -1;
    }
    
    function strStartWith(string memory haystack, string memory needle) pure internal returns (bool) {
        bytes memory a = bytes(haystack);
        bytes memory b = bytes(needle);
        if ( a.length < b.length  )
            return false;

        for (uint i = 0; i < b.length  ; i ++)
            if (a[i] != b[i])
              return false;
            
        return true;
    }
    
    function strEqual(string memory _a, string memory _b) pure internal returns (bool) {
        bytes memory a = bytes(_a);
        bytes memory b = bytes(_b);
        if (a.length != b.length)
            return false;

        for (uint i = 0; i < a.length; i ++)
            if (a[i] != b[i])
                return false;
                
        return true;
    }

    function strConcat(string _a, string _b, string _c, string _d, string _e) pure internal returns (string){
        bytes memory _ba = bytes(_a);
        bytes memory _bb = bytes(_b);
        bytes memory _bc = bytes(_c);
        bytes memory _bd = bytes(_d);
        bytes memory _be = bytes(_e);
        string memory abcde = new string(_ba.length + _bb.length + _bc.length + _bd.length + _be.length);
        bytes memory babcde = bytes(abcde);
        uint k = 0;
        for (uint i = 0; i < _ba.length; i++) babcde[k++] = _ba[i];
        for (i = 0; i < _bb.length; i++) babcde[k++] = _bb[i];
        for (i = 0; i < _bc.length; i++) babcde[k++] = _bc[i];
        for (i = 0; i < _bd.length; i++) babcde[k++] = _bd[i];
        for (i = 0; i < _be.length; i++) babcde[k++] = _be[i];
        return string(babcde);
    }
        
    function strConcat(string _a, string _b, string _c, string _d) pure internal returns (string) {
        return strConcat(_a, _b, _c, _d,"");
    }
    
    function strConcat(string _a, string _b, string _c) pure internal returns (string) {
        return strConcat(_a, _b, _c, "", "");
    }
    
    function strConcat(string _a, string _b) pure internal returns (string) {
        return strConcat(_a, _b, "", "", "");
    }

}