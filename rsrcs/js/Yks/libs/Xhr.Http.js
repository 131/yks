Xhr.encode_urlencode = function(hash){
    var str = [];
    hash.each(function(val){
        if($type(val) == 'string') str.push(val);
        else str.push(val.key + '=' + encodeURIComponent(val.value));
    });
    return str.join('&');
};


Xhr.implement({
 encode_urlencode: {
  transport_callback:'send',
  encode:function(hash, callback){
    var str = Xhr.encode_urlencode(hash);
    this.addHeaders({
        'Content-Type'   : 'application/x-www-form-urlencoded',
        'Content-Length' : str.length
    });
    callback(str);
  }
 }
});
