<?php

//CALLED BY THE CRONTAB. THIS SCRIPT CALLS ALL REQUIRED BACKORDER BATCHES.
include("batch_requested_active.php");
include("batch_active_processing.php");
include("batch_processing_application.php");
include("batch_polling.php");
