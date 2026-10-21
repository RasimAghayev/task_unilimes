<?php

declare(strict_types=1);

// The listing of the customers. The page works without JavaScript: the rows
// are rendered by the server from the same filters the JSON listing uses.
// With JavaScript the table is refreshed in place when a filter changes.

include_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/lib/CustomerRepository.php';

$repository = new CustomerRepository($db);
$listing = $repository->listCustomers($_GET);
$categories = $repository->categories();
$ageRange = $repository->ageRange();

$page = $listing['page'];
$limit = $listing['limit'];
$total = $listing['total'];
$pageCount = $limit > 0 ? (int) ceil($total / $limit) : 1;

/** The query string of the listing, without the page. */
$filterQuery = $_GET;
unset($filterQuery['page']);

$exportQuery = $filterQuery;

/** Escape a value of the database for the HTML. */
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

// The status of the last import.
$statusType = '';
$statusMsg = '';
if (! empty($_GET['status'])) {
    $imported = (int) ($_GET['imported'] ?? 0);
    $rejected = (int) ($_GET['rejected'] ?? 0);

    switch ($_GET['status']) {
        case 'succ':
            $statusType = 'alert-success';
            $statusMsg = $imported . ' row(s) imported'
                . ($rejected > 0 ? ', ' . $rejected . ' row(s) rejected.' : '.');
            break;
        case 'too_large':
            $statusType = 'alert-danger';
            $statusMsg = 'The file is larger than the import accepts.';
            break;
        case 'invalid_file':
            $statusType = 'alert-danger';
            $statusMsg = 'The file is not a CSV file with the expected columns.';
            break;
        default:
            $statusType = 'alert-danger';
            $statusMsg = 'Some problem occurred, please try again.';
    }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Customer listing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
  </head>
  <body>
<?php if ($statusMsg !== '') { ?>
<div class="col-xs-12" id="alert">
    <div class="alert <?= $escape($statusType) ?>"><?= $escape($statusMsg) ?></div>
</div>
<?php } ?>

<div class="row">
    <div class="col-md-9 head">
        <div class="float-left">
            <a href="javascript:void(0);" class="btn btn-success" onclick="formToggle('importFrm');"><i class="plus"></i> Import</a>
            <a id="export-link" class="btn btn-primary" href="exportData.php">Export CSV</a>
        </div>
        <div class="col-md-6" id="importFrm" style="display: none;">
            <form action="importData.php" method="post" enctype="multipart/form-data">
                <input type="file" name="file" accept=".csv,text/csv" />
                <input type="submit" class="btn btn-primary" name="importSubmit" value="IMPORT">
            </form>
        </div>
    </div>
    <div class="col-md-3 head">
        <nav id="pagination" aria-label="Table pages"></nav>
    </div>

    <table class="table table-striped table-bordered text-center">
        <thead class="thead-dark">
            <tr>
                <th><input type="text" id="id" name="id" placeholder="find id"></th>
                <th></th>
                <th><input type="text" id="firstname" name="firstname" placeholder="find firstname"></th>
                <th><input type="text" id="lastname" name="lastname" placeholder="find lastname"></th>
                <th>
                    <select name="gender" id="gender">
                        <option value=""></option>
                        <option value="female">female</option>
                        <option value="male">male</option>
                    </select>
                </th>
                <th><input type="text" id="email" name="email" placeholder="find email"></th>
                <th><input type="text" id="birthDate" name="birthDate" placeholder="find birthDate"></th>
                <th><input type="text" id="created_at" name="created_at" placeholder="find created_at"></th>
            </tr>
            <tr>
                <th>#ID</th>
                <th>category</th>
                <th>firstname</th>
                <th>lastname</th>
                <th>gender</th>
                <th>email</th>
                <th>birthDate</th>
                <th>created_at</th>
            </tr>
        </thead>
        <tbody id="customer-rows">
        <?php if (count($listing['rows']) > 0) {
            foreach ($listing['rows'] as $row) { ?>
            <tr>
                <td><?= $escape($row['id']) ?></td>
                <td><?= $escape($row['category']) ?></td>
                <td><?= $escape($row['firstname']) ?></td>
                <td><?= $escape($row['lastname']) ?></td>
                <td><?= $escape($row['gender']) ?></td>
                <td><?= $escape($row['email']) ?></td>
                <td><?= $escape($row['birthDate']) ?></td>
                <td><?= $escape($row['created_at']) ?></td>
            </tr>
        <?php }
        } else { ?>
            <tr><td colspan="8">No member(s) found...</td></tr>
        <?php } ?>
        </tbody>
        <tfoot class="tfoot-dark">
            <tr>
                <th><input type="text" id="idf" name="id" placeholder="find id"></th>
                <th></th>
                <th><input type="text" id="firstnamef" name="firstname" placeholder="find firstname"></th>
                <th><input type="text" id="lastnamef" name="lastname" placeholder="find lastname"></th>
                <th>
                    <select name="gender" id="genderf">
                        <option value=""></option>
                        <option value="female">female</option>
                        <option value="male">male</option>
                    </select>
                </th>
                <th><input type="text" id="emailf" name="email" placeholder="find email"></th>
                <th><input type="text" id="birthDatef" name="birthDate" placeholder="find birthDate"></th>
                <th><input type="text" id="created_atf" name="created_at" placeholder="find created_at"></th>
            </tr>
        </tfoot>
    </table>

    <p class="text-muted" id="summary">
        <?= $total ?> row(s) match the filter
        <?php if ($ageRange['minAge'] !== null) { ?>
            &middot; age between <?= $escape($ageRange['minAge']) ?> and <?= $escape($ageRange['maxAge']) ?>
        <?php } ?>
    </p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

<script>
function formToggle(ID){
    var element = document.getElementById(ID);
    if(element.style.display === "none"){
        element.style.display = "block";
    }else{
        element.style.display = "none";
    }
}
window.onload = function() {
    var duration = 2000; //2 seconds
    setTimeout(function () { $('#alert').hide(); }, duration);
};

(function () {
    'use strict';

    // The pairs of the filter inputs. The header of the table and its footer
    // hold the same filter twice, so both are read.
    var FIELDS = ['id', 'category', 'firstname', 'lastname', 'gender', 'email', 'birthDate', 'created_at'];

    var rows = document.getElementById('customer-rows');
    var summary = document.getElementById('summary');
    var pagination = document.getElementById('pagination');
    var exportLink = document.getElementById('export-link');

    var state = {
        page: <?= (int) $page ?>,
        limit: <?= (int) $limit ?>,
        total: <?= (int) $total ?>,
        pageCount: <?= (int) $pageCount ?>
    };

    function readFilters() {
        var filters = {};
        FIELDS.forEach(function (field) {
            var element = document.getElementById(field);
            if (!element) return;
            var value = (element.value || '').trim();
            if (value !== '') filters[field] = value;
        });
        return filters;
    }

    function buildQuery(filters, page) {
        var parts = [];
        Object.keys(filters).forEach(function (key) {
            parts.push(encodeURIComponent(key) + '=' + encodeURIComponent(filters[key]));
        });
        parts.push('page=' + encodeURIComponent(page));
        parts.push('limit=' + encodeURIComponent(state.limit));
        return parts.join('&');
    }

    // A cell is filled with a text node, so a value of the database can never
    // become markup.
    function fillCell(row, value) {
        var cell = document.createElement('td');
        cell.textContent = value === null || value === undefined ? '' : String(value);
        row.appendChild(cell);
    }

    function renderRows(data) {
        rows.textContent = '';
        if (!data || data.length === 0) {
            var empty = document.createElement('tr');
            var cell = document.createElement('td');
            cell.colSpan = 8;
            cell.textContent = 'No member(s) found...';
            empty.appendChild(cell);
            rows.appendChild(empty);
            return;
        }
        data.forEach(function (item) {
            var row = document.createElement('tr');
            fillCell(row, item.id);
            fillCell(row, item.category);
            fillCell(row, item.firstname);
            fillCell(row, item.lastname);
            fillCell(row, item.gender);
            fillCell(row, item.email);
            fillCell(row, item.birthDate);
            fillCell(row, item.created_at);
            rows.appendChild(row);
        });
    }

    function renderPagination() {
        pagination.textContent = '';

        var previous = document.createElement('button');
        previous.type = 'button';
        previous.className = 'btn btn-sm btn-secondary me-1';
        previous.textContent = 'Previous';
        previous.disabled = state.page <= 1;
        previous.addEventListener('click', function () { load(state.page - 1); });

        var label = document.createElement('span');
        label.className = 'me-2';
        label.textContent = 'Page ' + state.page + ' of ' + Math.max(state.pageCount, 1);

        var next = document.createElement('button');
        next.type = 'button';
        next.className = 'btn btn-sm btn-secondary';
        next.textContent = 'Next';
        next.disabled = state.page >= state.pageCount;
        next.addEventListener('click', function () { load(state.page + 1); });

        pagination.appendChild(previous);
        pagination.appendChild(label);
        pagination.appendChild(next);
    }

    function renderSummary(data) {
        var text = data.total + ' row(s) match the filter';
        if (data.ageMinMax && data.ageMinMax.minAge !== null) {
            text += ' · age between ' + data.ageMinMax.minAge + ' and ' + data.ageMinMax.maxAge;
        }
        summary.textContent = text;
    }

    function load(page) {
        var filters = readFilters();
        var query = buildQuery(filters, page);

        exportLink.href = 'exportData.php?' + query.replace(/&?page=[^&]*/, '').replace(/^&/, '');

        return fetch('restData.php?' + query, { headers: { 'Accept': 'application/json' } })
            .then(function (response) {
                if (!response.ok) throw new Error('The listing failed.');
                return response.json();
            })
            .then(function (data) {
                state.page = data.page;
                state.total = data.total;
                state.pageCount = Math.ceil(data.total / data.limit) || 1;
                renderRows(data.data);
                renderSummary(data);
                renderPagination();
            })
            .catch(function (error) {
                summary.textContent = error.message;
            });
    }

    var timer = null;
    function scheduleLoad() {
        if (timer) clearTimeout(timer);
        timer = setTimeout(function () { load(1); }, 250);
    }

    FIELDS.forEach(function (field) {
        var element = document.getElementById(field);
        if (!element) return;
        element.addEventListener('input', scheduleLoad);
        element.addEventListener('change', scheduleLoad);
    });

    renderPagination();
})();
</script>
</body>
</html>
