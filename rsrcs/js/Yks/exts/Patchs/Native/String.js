
var URIescaped =['&ks'+'pc;','%25','%26','%2B'].associate( ['%','&ks'+'pc;',"&","\\+"]);

String.prototype.old_split = String.prototype.split;
String.prototype.split = function(separator,limit){
	var tmp=this.old_split(separator);
	if(limit==undefined)return tmp;limit--;
	res=tmp.slice(0,limit);
	res[limit]=tmp.slice(limit).join(separator);
	return res;
}

String.implement({
  areplace: function(h) {
    var tmp = this;
    for(var k in h) tmp = tmp.replace(new RegExp(k,"g"),h[k]);
    return tmp;
  },

  trim: function(flag){
    if(!$defined(flag))flag="\\s";
    return this.replace(new RegExp('^['+flag+']+|['+flag+']+$','g'),'');
  }

});

