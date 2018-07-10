<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once('class.TicketAdminPage.php');
$page = new TicketAdminPage('Burning Flipside - Tickets');

$page->addWellKnownJS(JS_DATATABLE);
$page->addWellKnownJS(JS_CHART);
$page->addWellKnownCSS(CSS_DATATABLE);

$page->body .= '
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Gate Graphs</h1>
            </div>
        </div>
        <div class="row">
            <div class="accordion">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title"><a data-toggle="collapse" data-parent="#accordion" href="#used">Used/Unused</a></h4>
                    </div>
                    <div id="used" class="collapse show">
                        <div class="panel-body">
                            <canvas id="used_chart" height="150" width="300" style="width: 300px; height: 150px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
';

$page->printPage();
// vim: set tabstop=4 shiftwidth=4 expandtab:
?>

