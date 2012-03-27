function buttonfix() {
    var buttons = document.getElementsByTagName('button');
    for (var i=0; i<buttons.length; i++) {
        if(buttons[i].onclick) continue;

        buttons[i].onclick = function () {
            for(j=0; j<this.form.elements.length; j++)
                if( this.form.elements[j].tagName == 'button' )
                    this.form.elements[j].disabled = true;
            this.disabled=false;
            this.value = this.attributes.getNamedItem("value").nodeValue ;
        }
    }
}
$(document).ready(function() {
    window.attachEvent("onload", buttonfix);
})