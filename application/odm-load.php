<?php
/*
 * Copyright (C) 2000-2021. Stephen Lawrence
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

/**
 * Bootstrap file for setting the ABSPATH constant
 * and loading the configs/config.php file.
 *
 * If the configs/config.php file is not found then an error
 * will be displayed asking the visitor to set up the
 * configs/config.php file.
 *
 * Will also search for configs/config.php in OpenDocMans' parent
 * directory to allow the OpenDocMan directory to remain
 * untouched.
 *
 */

//if (file_exists(__DIR__ . '/configs/config.php')) {
//    // In the case of root folder calls
//    require('configs/config.php');
//} elseif (file_exists(__DIR__ . '/configs/docker-configs/config.php')) {
//    // In case we are running from Docker
//    require('configs/docker-configs/config.php');
//} elseif (file_exists(__DIR__ . '/../../config.php')) {
//    // In the case of subfolders
//    require('../../configs/config.php');
//} elseif (file_exists(__DIR__ . '/../../../configs/config.php')) {
//    // In the case of plugins
//    require('../../../configs/config.php');
//} else {
//    header('Location: index');
//}