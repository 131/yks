
Xhr.extend( {
  split_headers: function(str){
   var ret = {}, e;
   str.split("\n").each(function(line){    
    line = line.trim();
    if(!line) return;
    e = line.split(':',2)
    ret[e[0].toLowerCase().trim()] = e[1].trim();
   });
   return ret;
  },

  http_lnk:function(options, url, data, async_func){
    if($type(options)!="object")
        options = {method:options,async:true};
    if($type(data)=="object")
        data = $H(data);

    data = [data];
    var headers = options.headers || {};
    headers['Yks-Jsx'] = 1;
 
    if(options.method.toLowerCase() == 'post')
        data.push({key:'ks_flag', value:Jsx.security_flag});

    (new Xhr(options.async))
    .addEvent('success', async_func)
    .addHeaders(headers)
    .request(url, options.method, data );
  }
});


