$(document).ready(function () { 
  $('.data-table').each(function () { 
    let table = $(this); 
    table.DataTable({ 
      processing: true, 
      serverSide: true, 
      ajax: { 
        url: table.data('ajaxurl'), 
        type: 'POST' 
      }, 
      pageLength: 10, 
      lengthMenu: [ [10, 25, 50, 100], [10, 25, 50, 100] ], 
      searching: true, 
      ordering: true, 
      columnDefs: [ {
         targets: [0, -1], 
         orderable: false 
      } ], 
      language: {
        search: "", 
        searchPlaceholder: "Search..." 
      }, 
      drawCallback: function () { 
        if (typeof lucide !== 'undefined') {
          lucide.createIcons(); 
        } 
      } 
    }); 
  }); 
});