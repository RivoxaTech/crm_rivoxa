<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('invoices:recurring')->dailyAt('06:00');
