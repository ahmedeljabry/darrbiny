/* Global table enhancements: empty-state + minor styling helpers */
(function () {
  var t = (window.APP && window.APP.i18n) ? window.APP.i18n : { noData: 'No data' };

  // Set DataTables defaults early (if present), so page initializers pick it up.
  if (window.jQuery && jQuery.fn && (jQuery.fn.dataTable || jQuery.fn.DataTable)) {
    try {
      jQuery.extend(true, jQuery.fn.dataTable.defaults, {
        language: {
          emptyTable: t.noData,
          zeroRecords: t.noData
        }
      });
    } catch (e) { /* no-op */ }
  }

  function addEmptyStateRows() {
    var tables = document.querySelectorAll('table');
    tables.forEach(function (table) {
      // Skip DataTables-managed tables
      if (table.closest('.dataTables_wrapper') || table.classList.contains('dataTable')) return;

      var tbody = table.tBodies && table.tBodies[0];
      if (!tbody) return;

      var hasRealRows = Array.prototype.some.call(tbody.rows || [], function (tr) {
        if (!tr || tr.classList.contains('empty-row')) return false;
        var cells = tr.querySelectorAll('td,th');
        return cells && cells.length > 0;
      });

      if (!hasRealRows) {
        var colCount = 0;
        if (table.tHead && table.tHead.rows[0]) {
          colCount = table.tHead.rows[0].cells.length;
        }
        if (!colCount && tbody.rows[0]) {
          colCount = tbody.rows[0].cells.length;
        }
        if (!colCount) colCount = 1;

        var tr = document.createElement('tr');
        tr.className = 'empty-row';
        var td = document.createElement('td');
        td.colSpan = colCount;
        td.innerHTML = '<div class="table-empty text-muted text-center py-4">' + (t.noData || 'No data') + '</div>';
        tr.appendChild(td);
        tbody.appendChild(tr);
      }
    });

    // Ensure vertical alignment on all .table elements
    document.querySelectorAll('table.table').forEach(function (tbl) {
      tbl.classList.add('align-middle');
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', addEmptyStateRows);
  } else {
    addEmptyStateRows();
  }
})();

