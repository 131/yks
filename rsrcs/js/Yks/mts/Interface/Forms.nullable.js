Jsx.Form.Nullable = new Class({
  Occlude: 'Jsx.Form.Nullable',

  initialize:function(form){

    if(this.occlude(form)) return;

    form.getInputs().each(function(el){
        var togglable = el.type == 'text' || el.get('tag') == 'select';
        if(!togglable) return;
        var str = el.disabled?"(edit)":"(set null)";
        $n('div', {text:str}).inject(el, 'after').addEvent('click', function(){
            el.disabled = !el.disabled;
            var str = el.disabled?"(edit)":"(set null)";        
            this.set('text', str);
        });
    });

  }
});