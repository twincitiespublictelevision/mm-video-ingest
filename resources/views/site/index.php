<!DOCTYPE html>
<html>
<head>
  <title>The Ingestor</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/foundation/6.3.1/css/foundation.min.css"/>
  <link rel="stylesheet" href="/css/style.css"/>
  <link rel="stylesheet" href="https://cdn.datatables.net/1.10.12/css/jquery.dataTables.min.css"/>
</head>
<body>
<div class="row">
  <div class="small-12 columns">
    <h1>Welcome to the Ingestor!</h1>
    <hr/>
    <?php if (!empty($error)) {
      echo '<p class="error">'.$error.'</p>';
    } ?>
    <p>This table refreshes every minute automatically. No refresh required.</p>
    <table id="tasks" class="display" cellspacing="0" width="100%">
      <thead>
      <tr>
        <th>ID</th>
        <th>Show</th>
        <th>Title</th>
        <th>PBS Content Id</th>
        <th>Updated</th>
        <th>Status</th>
        <th>Reason</th>
      </tr>
      </thead>
    </table>
    <p><button class="button alert cancel-btn">Cancel Processing Jobs</button></p>
  </div>
</div>

<script src="//code.jquery.com/jquery-1.12.3.js"></script>
<script src="https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js"></script>
<script src="js/json-formatter.js" type="text/javascript"></script>
<script>
  $(document).ready(function() {
    function mmAdminLink(pbsContentId) {
      return '<a target="_blank" href="<?php echo env('MM_CONSOLE'); ?>/admin/asset/' + pbsContentId + '/core-data/">' + pbsContentId + '</a>';
    }

    var table = $('#tasks').DataTable({
      processing: true,
      serverSide: true,
      ajax: '/tasks',
      order: [[4, 'desc']], // Order by updated time by default
      createdRow: function (row, data, index) {

        if (data[3]) {
          $('td', row)
            .eq(3)
            .html(mmAdminLink(data[3]));

          $('td', row)
            .eq(2)
            .html(data[2]);
        }

        if (data[5].search(/failed/) !== -1 || data[4] === 'cancelled') {
          $('td', row).eq(5).addClass('failed');
        } else if (data[5] === 'done') {
          $('td', row).eq(5).addClass('delivered');
        } else {
          $('td', row).eq(5).addClass('in-process');
        }

        $('td', row).eq(5).html(
          $('td', row)
            .eq(5)
            .html()
            .replace(/_/g, ' ')
            .replace(
              /\w\S*/g,
              function(txt) {
                return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
              })
        );

        if (data[6]) {
          try {
            var formatter = new JSONFormatter(
              JSON.parse(data[6]),
              {open: 0}
            );

            $('td', row)
              .eq(6)
              .html(formatter.render());
          } catch (e) {
            // Ignore parse failures
          }
        }
      }
    });

    setInterval(function () {
      table.ajax.reload();
    }, 60000);

    $('.cancel-btn').click(function(e) {
      if (confirm('Are you sure?')) {
        window.location.href = '/cancel';
      }
    })
  });
</script>
</body>
</html>