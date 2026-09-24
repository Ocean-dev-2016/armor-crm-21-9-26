
function initEditor() {
  const snowEditor = document.getElementById('snow-editor');
  if (snowEditor) {
    new Quill(snowEditor, {
      theme: 'snow',
      modules: {
        toolbar: [
          [{ 'font': [] }, { 'size': [] }],
          ['bold', 'italic', 'underline', 'strike'],
          [{ 'color': [] }, { 'background': [] }],
          [{ 'script': 'super' }, { 'script': 'sub' }],
          [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
          [{ 'list': 'ordered' }, { 'list': 'bullet' }],
          [{ 'indent': '-1' }, { 'indent': '+1' }],
          [{ 'direction': 'rtl' }],
          [{ 'align': [] }],
          ['link', 'image', 'video'],
          ['clean']
        ]
      }
    });
  }

  const bubbleEditor = document.getElementById('bubble-editor');
  if (bubbleEditor) {
    new Quill(bubbleEditor, {
      theme: 'bubble'
    });
  }
}
