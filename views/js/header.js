document.addEventListener('DOMContentLoaded', function() {
  // Find the element where you want to insert your content.
  // The main header is usually inside a container with the ID "main-header" or similar classes.
  const mainHeader = document.querySelector('#main-div .content-div, #main #content');

  if (mainHeader) {
    // Create a new HTML element for your content
    const myContent = document.createElement('div', '');
    myContent.className = 'bootstrap';
    myContent.innerHTML =
      '    <div class="alert alert-danger alert-dismissible">' +
      '        <button type="button" class="close" data-dismiss="alert">×</button>' +
      '        <strong>Warning!</strong> This is a Bootstrap alert.' +
      '    </div>\n';

    // Insert the new element at the beginning of the header
    mainHeader.prepend(myContent);
  }
});
