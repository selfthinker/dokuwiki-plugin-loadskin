/**
 * hide switch button and submit on change of dropdown
 */
addInitEvent(function(){
    var frm = $('tpl__switcher');
    if(!frm) return;
    frm.elements['switch'].style.display = 'none';
    addEvent(frm.elements['tpl'],'change',function(e){
        frm.submit();
    });
});
