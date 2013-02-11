
var Xhr = new Class({
  Implements: [Events],
  Binds:['state_change', 'request'],

  encoding:false, //urlencode|multipart
  headers:$H({}),

  initialize:function(async, encoding){
    this.async = $defined(async) ? async : true;
    this.lnk   = new Browser.Request();
    this.encoding = encoding || 'urlencode';
  },

  request:function(url, method, data){   
  
    var encoder = this["encode_"+this.encoding]
    if(!encoder)
        throw "Unknow encoder";

    this.lnk.open( method||'GET' , url, this.async);
    this.lnk.onreadystatechange = this.state_change;

    data = this.data_cleanup(data||[]); //data is a list


    encoder.encode.call(this, data, function(data){
      if(method == 'GET')
          data = null;
      
      this.headers.each( function(val, key){
          this.lnk.setRequestHeader(key, val);
      }.bind(this));
      
      this.lnk[encoder.transport_callback](data);

      if((!this.async) && this.lnk.readyState==4)
        this.state_change();
    }.bind(this));

  },

  isSuccess: function(){
    return this.lnk.readyState == 4
           && ((this.lnk.status >= 200) && (this.lnk.status < 300));
  },


    //prepare data as a list of {key:key,value:value} pairs, ready to be encoded
  data_cleanup:function(data){
    var ret = [];
    data.each(function(data){
      switch ($type(data)){
        case 'string' : ret.push(data);break;
        case 'element': ret.extend(document.id(data).toQueryList()); break;
        case 'hash': ret.extend(Hash.toQueryList(data));break;
        case 'object':if(data.value != null) ret.push(data);break; //default
      }
    });
    return ret;
  },


  addHeaders:function(vals){
    $extend(this.headers, vals);
    return this;
  },


  state_change:function(){ 
    if(!this.isSuccess()) return false;
    this.lnk.onreadystatechange = $empty; //prevent dbl calls
    
    var responseHeaders = this.lnk.getAllResponseHeaders();
    
    if(!responseHeaders){
      var lnk = this.lnk;
      Array.each(['Content-Type'], function(name, index){
        responseHeaders  += name + ': ' + lnk.getResponseHeader(name);
      });
    }
    
    var headers = Xhr.split_headers(responseHeaders);

    var content_type = (headers['content-type'] || "text/xml").split(';')[0];
    var val;

    if(content_type=="application/json") val = Urls.jsx_eval(this.lnk.responseText);
    else if(!(/[^a-z]xml$/).test(content_type)) val = this.lnk.responseText;
    else {    
        val = this.lnk.responseXML;//prepare serialize for later, no other chance after here (BB)
        if(!val.xml && !window.XMLSerializer)
            val.xml = this.lnk.responseText;
    }
    this.fireEvent('success', [val, headers ] );
  }

});

if(Browser.Engine.name == 'webkit'){
  XMLHttpRequest.prototype.sendAsBinary = function(datastr) {
    function byteValue(x) {
      return x.charCodeAt(0) & 0xff;
    }
    var ords = Array.prototype.map.call(datastr, byteValue);
    var ui8a = new Uint8Array(ords);
    this.send(ui8a);
  }
}
