<?php
/*
mimetypes.php -  Exension to MIME-Type mapping, pretty much the same as mime.types from Apache
Copyright (C) 2002, 2003, 2004 Stephen Lawrence Jr., Khoa Nguyen
Copyright (C) 2007 Stephen Lawrence Jr., Jon Miner
Copyright (C) 2008-2010 Stephen Lawrence Jr.

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

// Read default MIME Types from System
$mimetypes=array();
global $mimetypes;
if (is_file("/etc/mime.types"))
{
    $handle = fopen("/etc/mime.types","r");
    while (!feof($handle))
    {
        $inline = fgets($handle,1024);
        $line = strtr($inline,"\t"," ");
        if ( $line != "\n" && strncmp($line,"#",1) != 0 )
        {
            $mimetype = strtok($line," ");
            while (($ext = rtrim(strtok(" "))) != "" )
            {
                $mimetypes[$ext] = $mimetype;
            }
        }
    }
    fclose($handle);
    $mimetypes['default'] = '';
}
else
{
// OpenDocMan defaults
    $mimetypes['ez']  = 'application/andrew-inset';
    $mimetypes['csm'] = 'application/cu-seeme';
    $mimetypes['cu']  = 'application/cu-seeme';
    $mimetypes['xla'] = 'application/vnd.ms-excel';
    $mimetypes['xlam'] = 'application/vnd.ms-excel.addin.macroEnabled.12';
    $mimetypes['xls'] = 'application/vnd.ms-excel';
    $mimetypes['xlsb'] = 'application/vnd.ms-excel.sheet.binary.macroEnabled.12';
    $mimetypes['xlsm'] = 'application/vnd.ms-excel.sheet.macroEnabled.12';
    $mimetypes['xlsx'] = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    $mimetypes['xlt'] = 'application/vnd.ms-excel';
    $mimetypes['xltm'] = 'application/vnd.ms-excel.template.macroEnabled';
    $mimetypes['xltx'] = 'application/vnd.openxmlformats-officedocument.spreadsheetml.template';
    $mimetypes['hqx'] = 'application/mac-binhex40';
    $mimetypes['cpt'] = 'application/mac-compactpro';
    $mimetypes['doc'] = 'application/msword';
    $mimetypes['docm'] = 'application/vnd.ms-word.document.macroEnabled.12';
    $mimetypes['docx'] = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    $mimetypes['dot'] = 'application/msword';
    $mimetypes['dotm'] = 'application/vnd.ms-word.template.macroEnabled.12';
    $mimetypes['dotx'] = 'application/vnd.openxmlformats-officedocument.wordprocessingml.template';
    $mimetypes['wrd'] = 'application/msword';
    $mimetypes['bin'] = 'application/octet-stream';
    $mimetypes['dms'] = 'application/octet-stream';
    $mimetypes['lha'] = 'application/octet-stream';
    $mimetypes['lzh'] = 'application/octet-stream';
    $mimetypes['exe'] = 'application/octet-stream';
    $mimetypes['class'] = 'application/octet-stream';
    $mimetypes['oda'] = 'application/oda';
    $mimetypes['pdf'] = 'application/pdf';
    $mimetypes['pgp'] = 'application/pgp';
    $mimetypes['ai']  = 'application/postscript';
    $mimetypes['eps'] = 'application/postscript';
    $mimetypes['ps']  = 'application/postscript';
    $mimetypes['pot'] = 'application/vnd.ms-powerpoint';
    $mimetypes['potm'] = 'application/vnd.ms-powerpoint.template.macroEnabled.12';
    $mimetypes['potx'] = 'application/vnd.openxmlformats-officedocument.presentationml.template';
    $mimetypes['ppa'] = 'application/vnd.ms-powerpoint';
    $mimetypes['ppam'] = 'application/vnd.ms-powerpoint.addin.macroEnabled.12';
    $mimetypes['pps'] = 'application/vnd.ms-powerpoint';
    $mimetypes['ppsm'] = 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12';
    $mimetypes['ppsx'] = 'application/vnd.openxmlformats-officedocument.presentationml.slideshow';
    $mimetypes['ppt'] = 'application/vnd.ms-powerpoint';
    $mimetypes['pptm'] = 'application/vnd.ms-powerpoint.presentation.macroEnabled.12';
    $mimetypes['pptx'] = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
    $mimetypes['rtf'] = 'application/rtf';
    $mimetypes['wp5'] = 'application/wordperfect5.1';
    $mimetypes['wk']  = 'application/x-123';
    $mimetypes['wz']  = 'application/x-Wingz';
    $mimetypes['bcpio'] = 'application/x-bcpio';
    $mimetypes['vcd'] = 'application/x-cdlink';
    $mimetypes['pgn'] = 'application/x-chess-pgn';
    $mimetypes['z']   = 'application/x-compress';
    $mimetypes['Z']   = 'application/x-compress';
    $mimetypes['cpio'] = 'application/x-cpio';
    $mimetypes['csh'] = 'application/x-csh';
    $mimetypes['deb'] = 'application/x-debian-package';
    $mimetypes['dcr'] = 'application/x-director';
    $mimetypes['dir'] = 'application/x-director';
    $mimetypes['dxr'] = 'application/x-director';
    $mimetypes['dvi'] = 'application/x-dvi';
    $mimetypes['gtar'] = 'application/x-gtar';
    $mimetypes['tgz'] = 'application/x-gtar';
    $mimetypes['gz']  = 'application/x-gzip';
    $mimetypes['hdf'] = 'application/x-hdf';
    $mimetypes['phtml'] = 'application/x-httpd-php';
    $mimetypes['pht'] = 'application/x-httpd-php';
    $mimetypes['php'] = 'application/x-httpd-php';
    $mimetypes['js']  = 'application/x-javascript';
    $mimetypes['skp'] = 'application/x-koan';
    $mimetypes['skd'] = 'application/x-koan';
    $mimetypes['skt'] = 'application/x-koan';
    $mimetypes['skm'] = 'application/x-koan';
    $mimetypes['latex'] = 'application/x-latex';
    $mimetypes['frm'] = 'application/x-maker';
    $mimetypes['maker'] = 'application/x-maker';
    $mimetypes['frame'] = 'application/x-maker';
    $mimetypes['fm']  = 'application/x-maker';
    $mimetypes['fb']  = 'application/x-maker';
    $mimetypes['book'] = 'application/x-maker';
    $mimetypes['fbdoc'] = 'application/x-maker';
    $mimetypes['mif'] = 'application/x-mif';
    $mimetypes['com'] = 'application/x-msdos-program';
    $mimetypes['exe'] = 'application/x-msdos-program';
    $mimetypes['bat'] = 'application/x-msdos-program';
    $mimetypes['nc']  = 'application/x-netcdf';
    $mimetypes['cdf'] = 'application/x-netcdf';
    $mimetypes['pac'] = 'application/x-ns-proxy-autoconfig';
    $mimetypes['pl']  = 'application/x-perl';
    $mimetypes['pm']  = 'application/x-perl';
    $mimetypes['sh']  = 'application/x-sh';
    $mimetypes['shar'] = 'application/x-shar';
    $mimetypes['sit'] = 'application/x-stuffit';
    $mimetypes['sv4cpio'] = 'application/x-sv4cpio';
    $mimetypes['sv4crc'] = 'application/x-sv4crc';
    $mimetypes['tar'] = 'application/x-tar';
    $mimetypes['tcl'] = 'application/x-tcl';
    $mimetypes['tex'] = 'application/x-tex';
    $mimetypes['texinfo'] = 'application/x-texinfo';
    $mimetypes['texi'] = 'application/x-texinfo';
    $mimetypes['t']   = 'application/x-troff';
    $mimetypes['tr']  = 'application/x-troff';
    $mimetypes['roff'] = 'application/x-troff';
    $mimetypes['man'] = 'application/x-troff-man';
    $mimetypes['me']  = 'application/x-troff-me';
    $mimetypes['ms']  = 'application/x-troff-ms';
    $mimetypes['ustar'] = 'application/x-ustar';
    $mimetypes['src'] = 'application/x-wais-source';
    $mimetypes['zip'] = 'application/zip';
    $mimetypes['au']  = 'audio/basic';
    $mimetypes['snd'] = 'audio/basic';
    $mimetypes['mid'] = 'audio/midi';
    $mimetypes['midi'] = 'audio/midi';
    $mimetypes['kar'] = 'audio/midi';
    $mimetypes['mpga'] = 'audio/mpeg';
    $mimetypes['mp2'] = 'audio/mpeg';
    $mimetypes['mp3'] = 'audio/mpeg';
    $mimetypes['aif'] = 'audio/x-aiff';
    $mimetypes['aifc'] = 'audio/x-aiff';
    $mimetypes['aiff'] = 'audio/x-aiff';
    $mimetypes['ram'] = 'audio/x-pn-realaudio';
    $mimetypes['ra']  = 'audio/x-realaudio';
    $mimetypes['wav'] = 'audio/x-wav';
    $mimetypes['pdb'] = 'chemical/x-pdb';
    $mimetypes['xyz'] = 'chemical/x-pdb';
    $mimetypes['gif'] = 'image/gif';
    $mimetypes['ief'] = 'image/ief';
    $mimetypes['jpeg'] = 'image/jpeg';
    $mimetypes['jpg'] = 'image/jpeg';
    $mimetypes['jpe'] = 'image/jpeg';
    $mimetypes['png'] = 'image/png';
    $mimetypes['tiff'] = 'image/tiff';
    $mimetypes['tif'] = 'image/tiff';
    $mimetypes['ras'] = 'image/x-cmu-raster';
    $mimetypes['pnm'] = 'image/x-portable-anymap';
    $mimetypes['pbm'] = 'image/x-portable-bitmap';
    $mimetypes['pgm'] = 'image/x-portable-graymap';
    $mimetypes['ppm'] = 'image/x-portable-pixmap';
    $mimetypes['rgb'] = 'image/x-rgb';
    $mimetypes['xbm'] = 'image/x-xbitmap';
    $mimetypes['xpm'] = 'image/x-xpixmap';
    $mimetypes['xwd'] = 'image/x-xwindowdump';
    $mimetypes['igs'] = 'model/iges';
    $mimetypes['iges'] = 'model/iges';
    $mimetypes['msh'] = 'model/mesh';
    $mimetypes['mesh'] = 'model/mesh';
    $mimetypes['silo'] = 'model/mesh';
    $mimetypes['wrl'] = 'model/vrml';
    $mimetypes['vrml'] = 'model/vrml';
    $mimetypes['css'] = 'text/css';
    $mimetypes['html'] = 'text/html';
    $mimetypes['htm'] = 'text/html';
    $mimetypes['asc'] = 'text/plain';
    $mimetypes['txt'] = 'text/plain';
    $mimetypes['c']   = 'text/plain';
    $mimetypes['cc']  = 'text/plain';
    $mimetypes['h']   = 'text/plain';
    $mimetypes['hh']  = 'text/plain';
    $mimetypes['cpp'] = 'text/plain';
    $mimetypes['hpp'] = 'text/plain';
    $mimetypes['java'] = 'text/plain';
    $mimetypes['rtx'] = 'text/richtext';
    $mimetypes['tsv'] = 'text/tab-separated-values';
    $mimetypes['etx'] = 'text/x-setext';
    $mimetypes['sgml'] = 'text/x-sgml';
    $mimetypes['sgm'] = 'text/x-sgml';
    $mimetypes['vcs'] = 'text/x-vCalendar';
    $mimetypes['vcf'] = 'text/x-vCard';
    $mimetypes['xml'] = 'text/xml';
    $mimetypes['dtd'] = 'text/xml';
    $mimetypes['dl']  = 'video/dl';
    $mimetypes['fli'] = 'video/fli';
    $mimetypes['gl']  = 'video/gl';
    $mimetypes['mp2'] = 'video/mpeg';
    $mimetypes['mpe'] = 'video/mpeg';
    $mimetypes['mpeg'] = 'video/mpeg';
    $mimetypes['mpg'] = 'video/mpeg';
    $mimetypes['qt']  = 'video/quicktime';
    $mimetypes['mov'] = 'video/quicktime';
    $mimetypes['avi'] = 'video/x-msvideo';
    $mimetypes['movie'] = 'video/x-sgi-movie';
    $mimetypes['ice'] = 'x-conference/x-cooltalk';
    $mimetypes['qdf'] = 'application/octet-stream';
    $mimetypes['odt'] = 'application/vnd.oasis.opendocument.text';
    $mimetypes['odp'] = 'application/vnd.oasis.opendocument.presentation';
    $mimetypes['ods'] = 'application/vnd.oasis.opendocument.spreadsheet';
    $mimetypes['sxw'] = 'application/vnd.sun.xml.writer';
    $mimetypes['sxc'] = 'application/vnd.sun.xml.calc';
    $mimetypes['sxd'] = 'application/vnd.sun.xml.draw';
    $mimetypes['sxi'] = 'application/vnd.sun.xml.impress';
    $mimetypes['sxm'] = 'application/vnd.sun.xml.math';
    $mimetypes['ott'] = 'application/vnd.oasis.opendocument.text-template';
    $mimetypes['odg'] = 'application/vnd.oasis.opendocument.graphics';
    $mimetypes['odc'] = 'application/vnd.oasis.opendocument.chart';
// http://framework.openoffice.org/documentation/mimetypes/mimetypes.html
    $mimetypes['odb'] = 'application/vnd.oasis.opendocument.database';
    $mimetypes['odc'] = 'application/vnd.oasis.opendocument.chart';
    $mimetypes['odf'] = 'application/vnd.oasis.opendocument.formula';
    $mimetypes['odg'] = 'application/vnd.oasis.opendocument.graphics';
    $mimetypes['odi'] = 'application/vnd.oasis.opendocument.image';
    $mimetypes['odm'] = 'application/vnd.oasis.opendocument.text-master';
    $mimetypes['odp'] = 'application/vnd.oasis.opendocument.presentation';
    $mimetypes['ods'] = 'application/vnd.oasis.opendocument.spreadsheet';
    $mimetypes['odt'] = 'application/vnd.oasis.opendocument.text';
    $mimetypes['otg'] = 'application/vnd.oasis.opendocument.graphics-template';
    $mimetypes['oth'] = 'application/vnd.oasis.opendocument.text-web';
    $mimetypes['otp'] = 'application/vnd.oasis.opendocument.presentation-template';
    $mimetypes['ots'] = 'application/vnd.oasis.opendocument.spreadsheet-template';
    $mimetypes['ott'] = 'application/vnd.oasis.opendocument.text-template';
    $mimetypes['sda'] = 'application/vnd.stardivision.draw';
    $mimetypes['sdc'] = 'application/vnd.stardivision.calc';
    $mimetypes['sdd'] = 'application/vnd.stardivision.impress';
    $mimetypes['sdw'] = 'application/vnd.stardivision.writer';
    $mimetypes['sgl'] = 'application/vnd.stardivision.writer-global';
    $mimetypes['smf'] = 'application/vnd.stardivision.math';
    $mimetypes['stc'] = 'application/vnd.sun.xml.calc.template';
    $mimetypes['std'] = 'application/vnd.sun.xml.draw.template';
    $mimetypes['sti'] = 'application/vnd.sun.xml.impress.template';
    $mimetypes['stw'] = 'application/vnd.sun.xml.writer.template';
    $mimetypes['sxc'] = 'application/vnd.sun.xml.calc';
    $mimetypes['sxd'] = 'application/vnd.sun.xml.draw';
    $mimetypes['sxg'] = 'application/vnd.sun.xml.writer.global';
    $mimetypes['sxi'] = 'application/vnd.sun.xml.impress';
    $mimetypes['sxm'] = 'application/vnd.sun.xml.math';
    $mimetypes['sxw'] = 'application/vnd.sun.xml.writer';

    $mimetypes['default'] = '';
}