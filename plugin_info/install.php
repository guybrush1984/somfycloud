<?php
/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

function somfycloud_install() {
    $cron = cron::byClassAndFunction('somfycloud', 'poll');
    if (!is_object($cron)) {
        $cron = new cron();
        $cron->setClass('somfycloud');
        $cron->setFunction('poll');
        $cron->setEnable(1);
        $cron->setDeamon(0);
        $cron->setSchedule('*/5 * * * *');
        $cron->save();
    }
    message::add('somfycloud', __('Installation du plugin Somfy Cloud terminee', __FILE__));
}

function somfycloud_update() {
    $cron = cron::byClassAndFunction('somfycloud', 'poll');
    if (!is_object($cron)) {
        $cron = new cron();
        $cron->setClass('somfycloud');
        $cron->setFunction('poll');
        $cron->setEnable(1);
        $cron->setDeamon(0);
        $cron->setSchedule('*/5 * * * *');
        $cron->save();
    }
    message::add('somfycloud', __('Mise a jour du plugin Somfy Cloud terminee', __FILE__));
}

function somfycloud_remove() {
    $cron = cron::byClassAndFunction('somfycloud', 'poll');
    if (is_object($cron)) {
        $cron->remove();
    }
    cache::delete('somfycloud::jsessionid');
    cache::delete('somfycloud::listenerId');
    message::add('somfycloud', __('Suppression du plugin Somfy Cloud terminee', __FILE__));
}
