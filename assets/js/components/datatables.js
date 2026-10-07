$(document).ready(function () {
  $('.data-table').each(function () {
    let table = $(this);
    let filterFormSelector = table.data('filter-form');
    table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: table.data('ajaxurl'),
        type: 'POST',
        data: function (d) {
          if (filterFormSelector && $(filterFormSelector).length) {
            let formArray = $(filterFormSelector).serializeArray();
            $.each(formArray, function (i, field) {
              d[field.name] = field.value;
            });
          }
          if (typeof window.dataTableExtraData === 'function') {
            let extra = window.dataTableExtraData(table);
            if (extra && typeof extra === 'object') {
              $.extend(d, extra);
            }
          }
        }
      },
      pageLength: 10,
      lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
      searching: true,
      ordering: true,
      autoWidth: false,
      columnDefs: [{
        targets: [0, -1],
        orderable: false
      }],
      language: {
        search: "",
        searchPlaceholder: "Search..."
      },
      createdRow: function (row, data, dataIndex) {
        $(row).find('td').each(function () {
          let text = $(this).text().trim();
          if (text.indexOf('@') !== -1 && text.indexOf('.') !== -1) {
            $(this).addClass('email-col');
          }
        });
      },
      drawCallback: function () {
        let emailIdx = [];
        table.find('thead th').each(function (idx) {
          if ($(this).text().trim().toLowerCase().indexOf('email') !== -1) {
            emailIdx.push(idx);
          }
        });
        if (emailIdx.length > 0) {
          table.find('tbody tr').each(function () {
            let cells = $(this).find('td');
            emailIdx.forEach(function (i) {
              cells.eq(i).addClass('email-col');
            });
          });
        }
        if (typeof lucide !== 'undefined') {
          lucide.createIcons();
        }
      }
    });
  });
});