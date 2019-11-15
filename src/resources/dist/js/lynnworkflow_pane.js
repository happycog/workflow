/**
 * Lynn Workflow pane
 */
console.log('Workflow pane is here');

/* These are the modifications: https://stackoverflow.com/questions/6390341/how-to-detect-url-change-in-javascript/52809105#52809105 */
history.pushState = ( f => function pushState(){
    var ret = f.apply(this, arguments);
    window.dispatchEvent(new Event('pushstate'));
    window.dispatchEvent(new Event('locationchange'));
    return ret;
})(history.pushState);

history.replaceState = ( f => function replaceState(){
    var ret = f.apply(this, arguments);
    window.dispatchEvent(new Event('replacestate'));
    window.dispatchEvent(new Event('locationchange'));
    return ret;
})(history.replaceState);

window.addEventListener('popstate',()=>{
    window.dispatchEvent(new Event('locationchange'));
});




window.addEventListener('locationchange', function(){
  console.log('location changed!');
  draftState(true);
});

function draftState(chagned){
  /* `currentId` is a global that should have been set in 'workflow-pane.html' */
  var urlParams = new URLSearchParams(window.location.search);

  console.log('is draft: ' + urlParams.has('draftId'));
  if(urlParams.has('draftId')){
    $('#workflow-widget').show();
    if(chagned && currentId){
      // load external sidebar pane (need entryId and draftId)
      $("#workflow-pane").load("/lynnedu_admin/lynnworkflow/submissions/sidebar/" + currentId + "/" + urlParams.get("draftId"));
    }
  }else{
    $('#workflow-widget').hide();
  }
}

draftState(false);