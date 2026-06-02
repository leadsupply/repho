<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('package:sync')->hourly();
