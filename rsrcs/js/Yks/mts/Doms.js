
var Doms = {
  box_mask: ".box",
  loaders: {},
  context: $(window.document.documentElement),
  scan: function(context){
    var infos;
    context = $(context || window.document.documentElement);
    for(var uid in this.loaders) { infos = this.loaders[uid];
        if(!infos.match) continue;
        context.getElements(infos.match).each(
          function(el){ this.instanciate(infos['class'], el);}.bind(this));
    } var focus = context.getElement('.autofocus'); if(focus) focus.focus();
  },
  
  instanciate: function(klass){
    var args = Array.slice(arguments, 1);
    if($type(klass)=='class')
        return new klass(args[0], args[1], args[2]);

    if($type(klass)=='string' && Doms.autoload(klass) )
        return new ($take(window, klass.split('.')))(args[0], args[1], args[2]);
    //throw
  },
  
  wake: function(klass){
    var args = Array.slice(arguments, 1);
    if($type(klass)=='string' && ( window[klass] || Doms.autoload(klass)) )
        return window[klass];
    //throw
  },
  
  autoload: function(name) {
    if($take(window, name.split('.'))) return true;
    for(var uid in this.loaders) {
        var infos = this.loaders[uid];
        if(infos['class'] != name)continue;
        var args = {uid:uid, ks_action:'load_js'}, url ='/?/Yks/Scripts/Js';
        http_lnk({method:'post', async:false},url , args, $exec);

        var tmp = $take(window, name.split('.'));
        if(!tmp) return false;
        this.loaders[uid]['class'] = tmp;
        return true;
    } return false;
  }
};

