<?php
/**
 * Class for display sourcecode.
 *
 * @author Mikael Roos
 * @copyright Mikael Roos 2010-2015
 * @link   https://github.com/mosbth/csource
 */

//Favicon
$fav = "m02/Favicon-a.jpg";
echo "<link rel='icon' type='image/jpg' href='$fav'>";
class CSource
{
    private $options = [];
    private $validImageExtensions;
    private $spaces;
    private $ignore;
    private $secureDir;
    private $baseDir;
    private $queryPath;
    private $suggestedPath;
    private $realPath;
    private $pathinfo;
    private $path;
    private $file;
    private $extension;
    private $dir;
    private $breadcrumb;
    private $message;
    private $content;
    private $encoding;
    private $lineendings;

    private static $typeColumnScriptInjected = false;

    private function normalizePath(?string $path): ?string{
        if($path === null){
            return null;
        }
        $trimmed = trim($path, '/');
        return $trimmed === '' ? null : $trimmed;
    }

    public function __construct($options = [])
    {
        $default = [
                'image_extensions' => ['png','jpg','jpeg','gif','ico'],
                'spaces_to_replace_tab' => '  ',
                'ignore' => ['.','..','.git','.svn','.netrc','.ssh'],
                'add_ignore' => null,
                'secure_dir' => '.',
                'base_dir'   => '.',
                'query_dir'  => isset($_GET['dir'])  ? strip_tags(trim((string)$_GET['dir']))   : '',
                'query_file' => isset($_GET['file']) ? strip_tags(trim((string)$_GET['file']))  : '',
                'query_path' => isset($_GET['path']) ? strip_tags(trim((string)$_GET['path'])) : null,
        ];

        if(isset($options['add_ignore'])){
            $default['ignore'] = array_merge($default['ignore'],$options['add_ignore']);
        }

        $this->options = $options = array_merge($default,$options);

        if(!isset($this->options['query_path']) || $this->options['query_path'] === ''){
            $joined = trim($this->options['query_dir'].'/'.$this->options['query_file'], '/');
            $this->options['query_path'] = $joined === '' ? null : $joined;
        }

        $this->validImageExtensions = $options['image_extensions'];
        $this->spaces = $options['spaces_to_replace_tab'];
        $this->ignore = $options['ignore'];
        $this->secureDir = realpath($options['secure_dir']);
        $this->baseDir = realpath($options['base_dir']);
        $this->queryPath = $options['query_path'];

        $this->suggestedPath = str_replace(['\\','/'], DIRECTORY_SEPARATOR, $this->baseDir.DIRECTORY_SEPARATOR.$this->queryPath);
        $this->realPath = realpath($this->suggestedPath);
        $this->pathinfo = $this->realPath ? pathinfo($this->realPath) : [];
        $this->path = null;

        if(!isset($this->pathinfo['extension'])) $this->pathinfo['extension']=null;

        if($this->realPath && is_dir($this->realPath)){
            $this->file=null;
            $this->extension=null;
            $this->dir=$this->realPath;
            $this->path=$this->normalizePath($this->queryPath);
        } else if($this->realPath && is_link($this->suggestedPath)){
            $this->pathinfo=pathinfo($this->suggestedPath);
            $this->file=$this->pathinfo['basename'];
            $this->extension=strtolower($this->pathinfo['extension']);
            $this->dir=$this->pathinfo['dirname'];
            $parentPath = ($this->queryPath !== null && $this->queryPath !== '') ? dirname($this->queryPath) : null;
            $this->path=$this->normalizePath($parentPath);
        } else if($this->realPath && is_readable($this->realPath)){
            $this->file=basename($this->realPath);
            $this->extension=strtolower($this->pathinfo['extension']);
            $this->dir=dirname($this->realPath);
            $parentPath = ($this->queryPath !== null && $this->queryPath !== '') ? dirname($this->queryPath) : null;
            $this->path=$this->normalizePath($parentPath);
        } else {
            $this->file=null;
            $this->extension=null;
            $this->dir=null;
        }

        if($this->path=='.') $this->path=null;
        $this->breadcrumb = empty($this->path)?[]:explode('/',$this->path);

        $this->message=null;
        $msg="<p><i>WARNING: The path you have selected is not a valid path or restricted due to security constraints.</i></p>";

        if(!$this->dir || strpos($this->dir,$this->secureDir)!==0){
            $this->file=null;
            $this->extension=null;
            $this->dir=null;
            $this->message=$msg;
        }

        foreach($this->breadcrumb as $val){
            if(in_array($val,$this->ignore)){
                $this->file=null;
                $this->extension=null;
                $this->dir=null;
                $this->message=$msg;
                break;
            }
        }
    }

    private function shouldIgnore($basename,$relative,$absolute=null){
        foreach($this->ignore as $ignore){
            $ignoreNorm=str_replace('\\','/',$ignore);
            $basenameNorm=str_replace('\\','/',$basename);
            $relativeNorm=str_replace('\\','/',$relative);
            $absoluteNorm=$absolute?str_replace('\\','/',$absolute):null;

            if($basenameNorm===$ignoreNorm || $relativeNorm===$ignoreNorm || $absoluteNorm===$ignoreNorm) return true;
            if(fnmatch($ignoreNorm,$basenameNorm) || fnmatch($ignoreNorm,$relativeNorm) || ($absoluteNorm !== null && fnmatch($ignoreNorm,$absoluteNorm))) return true;
        }
        return false;
    }

    public function getFileType($filename){
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        switch($ext){
            case 'php': return 'PHP Fil';
            case 'html':
            case 'htm': return 'HTML Fil';
            case 'py': return 'PYTHON Fil';
            case 'css': return 'CSS File';
            case 'js': return 'JavaScript Fil';
            case 'txt': return 'Plain Text Fil';
            case 'svg': return 'SVG Bild';
            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'gif':
            case 'ico': return 'Image/Bild Fil';
            default: return $ext ? strtoupper($ext).' Fil' : 'Mapp';
        }
    }

    public function view(){
        return $this->getBreadcrumbFromPath() . $this->message .$this->readCurrentDir().$this->getFileContent();
    }

    public function getBreadcrumbFromPath(){
        $html="<ul class='src-breadcrumb'>\n";
        $homeLabel = htmlspecialchars(basename($this->baseDir), ENT_QUOTES, 'UTF-8');
        $html.="<li><a href='?'>".$homeLabel."</a>/</li>";
        $path=null;
        foreach($this->breadcrumb as $val){
            $path.="$val/";
            $label = htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
            $href = '?path='.rawurlencode($path);
            $html.="<li><a href='".htmlspecialchars($href, ENT_QUOTES, 'UTF-8')."'>".$label."</a>/</li>";
        }
        $html.="</ul>\n";
        return $html;
    }

    public function readCurrentDir(){
        if(!$this->dir) return;

        $allFiles = glob($this->dir.'/{*,.?*}',GLOB_MARK|GLOB_BRACE);
        if($allFiles === false){
            $allFiles = [];
        }
        $items = [];

        foreach($allFiles as $val){
            $basename = basename($val);
            $relative = trim(str_replace($this->baseDir,'',$val),'/');
            $absolute = realpath($val);

            if($this->shouldIgnore($basename,$relative,$absolute)) continue;

            $items[] = [
                'basename' => $basename,
                'relative' => $relative,
                'is_dir'   => is_dir($val),
            ];
        }

        // Sort alphabetically by name
        usort($items, function($a, $b){
            return strcasecmp($a['basename'], $b['basename']);
        });

        $html  = "<div class='file-table-wrap'><table class='file-table'>";
        $html .= "<thead><tr><th>Namn</th><th>Typ</th><th>Storlek</th></tr></thead><tbody>";

        foreach($items as $item){
            $displayName = htmlspecialchars($item['basename'], ENT_QUOTES, 'UTF-8');
            $pathPrefix = ($this->path !== null && $this->path !== '') ? $this->path.'/' : '';
            $pathRaw = $pathPrefix . $item['basename'];
            $href = '?path='.rawurlencode($pathRaw);
            $sizeDisp = '';
            $full = $this->baseDir.DIRECTORY_SEPARATOR.$pathRaw;
            $real = realpath($full);
            if($real && is_readable($real)){
                if($item['is_dir']){
                    $dirBytes = $this->getDirectoryBytes($real);
                    if($dirBytes !== null){
                        $sizeDisp = $this->formatBytes($dirBytes);
                    }
                } else {
                    $size = @filesize($real);
                    if($size !== false){
                        $sizeDisp = $this->formatBytes($size);
                    }
                }
            }
            $rowClass = $item['is_dir'] ? 'is-dir' : 'is-file';
            $type = $item['is_dir'] ? '' : $this->getFileType($item['basename']);
            $html .= "<tr class='".htmlspecialchars($rowClass, ENT_QUOTES, 'UTF-8')."'>"
                ."<td><a href='".htmlspecialchars($href, ENT_QUOTES, 'UTF-8')."'><span class='link-label'>".$displayName."</span></a></td>"
                ."<td>".htmlspecialchars($type, ENT_QUOTES, 'UTF-8')."</td>"
                ."<td>".htmlspecialchars($sizeDisp, ENT_QUOTES, 'UTF-8')."</td>"
                ."</tr>";
        }

        $html .= "</tbody></table></div>";

        if(!self::$typeColumnScriptInjected){
            $html .= <<<SCRIPT
<script>
(function(){
    function updateTypeColumns(){
        document.querySelectorAll('.file-table').forEach(function(table){
            var hasFile = !!table.querySelector('tbody tr.is-file');
            var cells = table.querySelectorAll('thead th:nth-child(2), tbody td:nth-child(2)');
            cells.forEach(function(cell){
                cell.style.display = hasFile ? '' : 'none';
            });
        });
    }
    if(document.readyState === 'loading'){
        document.addEventListener('DOMContentLoaded', updateTypeColumns);
    } else {
        updateTypeColumns();
    }
})();
</script>
SCRIPT;
            self::$typeColumnScriptInjected = true;
        }

        return $html;
    }

    private function formatBytes($bytes, $precision = 1){
        $units = ['B','KB','MB','GB','TB'];
        $bytes = max($bytes, 0);
        $pow = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    private function getDirectoryBytes($dir){
        if(!$dir || !is_dir($dir) || !is_readable($dir)){
            return null;
        }
        $total = 0;
        try{
            $flags = \FilesystemIterator::SKIP_DOTS;
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, $flags),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            foreach($iterator as $file){
                // Skip if not a regular file
                if(!$file->isFile()) continue;
                $absolute = $file->getPathname();
                $basename = $file->getFilename();
                $relative = trim(str_replace($this->baseDir, '', $absolute), '/');
                if($this->shouldIgnore($basename, $relative, $absolute)) continue;
                $size = @filesize($absolute);
                if($size !== false){ $total += $size; }
            }
        } catch(\Throwable $e){
            // Swallow exceptions (permissions, etc.) and return what we have
        }
        return $total;
    }

    public function detectFileDetails(){
        $this->encoding=null;
        if(function_exists('mb_detect_encoding')){
            if($res=mb_detect_encoding($this->content,"auto, ISO-8859-1",true)){
                $this->encoding=$res;
            }
        }
        if(substr($this->content,0,3)== chr(0xEF).chr(0xBB).chr(0xBF)) $this->encoding.=" BOM";

        $this->lineendings=null;
        if(isset($this->encoding) && $this->content){
            $lines=explode("\n",$this->content);
            $len=strlen($lines[0]);
            if(substr($lines[0],$len-1,1)=="\r"){
                $this->lineendings=" Windows (CRLF) ";
            } else $this->lineendings=" Unix (LF) ";
        }
    }

    public function filterPasswords(){
        $pattern=[
                '/(\'|")(DB_PASSWORD|DB_USER|DB_NAME|DB_PASS)(.+)/',
                '/\$(password|passwd|pass|pwd|pw|user|username)(\s*=\s*)(\'|")(.+)/i',
                '/(\'|")(password|passwd|pass|pwd|pw|user|username)(\'|")(\s*=>\s*)(\'|")(.+)([\'|"].*)/i',
                '/(\[[\'|"])(password|passwd|pass|pwd|pw|user|username)([\'|"]\])(\s*=\s*)(\'|")(.+)([\'|"].*)/i',
        ];
        $message="Intentionally removed by CSource";
        $replace=[
                '\1\2\1,  "'.$message.'");',
                '$\1\2\3'.$message.'\3;',
                '\1\2\3\4\5'.$message.'\7',
                '\1\2\3\4\5'.$message.'\7',
        ];
        $this->content=preg_replace($pattern,$replace,$this->content);
    }

    public function getFileContent(){
        if(!isset($this->file)) return;

        $basename=$this->file;
        $relative=ltrim((($this->path !== null && $this->path !== '') ? $this->path.'/' : '').$this->file,'/');
        $absolute=$this->realPath;

        if($this->shouldIgnore($basename,$relative,$absolute)){
            return "<p><i>Access to this file is restricted.</i></p>";
        }

        if(!file_exists($this->realPath)){
            return "<p><i>File not found: {$this->realPath}</i></p>";
        }

        $this->content=file_get_contents($this->realPath);
        $this->detectFileDetails();
        $this->filterPasswords();

        $linkToDisplaySvg="";
        if($this->extension=='svg'){
            if(isset($_GET['displaysvg'])){
                header("Content-type: image/svg+xml");
                echo $this->content;
                exit;
            } else {
                $safeUri = htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8');
                $linkToDisplaySvg="<a href='".$safeUri."&displaysvg'>Display as SVG</a>";
            }
        }

        if(in_array($this->extension,$this->validImageExtensions)){
            $baseDir=!empty($this->options['base_dir'])? rtrim($this->options['base_dir'], '/') . '/' : '';
            $imgPath = $baseDir.(($this->path !== null && $this->path !== '') ? $this->path.'/' : '').$this->file;
            $this->content="<div style='overflow:auto;'><img src='".htmlspecialchars($imgPath, ENT_QUOTES, 'UTF-8')."' alt='[image not found]'></div>";
        } else {
            $this->content=str_replace("\t",$this->spaces,$this->content);
            $this->content=highlight_string($this->content,true);
            $i=0;
            $rownums="";
            $text="";
            $content=explode('<br />',$this->content);
            foreach($content as $row){
                $i++;
                $rownums.="<code><a id='L{$i}' href='#L{$i}'>{$i}</a></code><br />";
                $text.=$row.'<br />';
            }

            $fileType = $this->getFileType($this->file);

            $this->content=<<<EOD
<div class='src-container'>
<div class='src-header'><code>{$i} lines {$this->encoding} {$this->lineendings} {$linkToDisplaySvg} - Type: {$fileType}</code></div>
<div class='src-rows'>{$rownums}</div>
<div class='src-code'>{$text}</div>
</div>
EOD;
        }

        $fileHeading = htmlspecialchars($this->file, ENT_QUOTES, 'UTF-8');
        return "<h3 id='file'><code><a href='#file'>".$fileHeading."</a></code></h3>{$this->content}";
    }
}

//Ignore files and directories
$source = new CSource([
        'add_ignore'=>[
                'C:/R/P/U/KL.html',
                'FF/NH/PO.html',
                'c.html',
                'ji.php',
                'welcome-to-docker',
                'Yeic.html',
                'TEst1',
                'm02/style.css',
                'CSource.php',
                'm02/Favicon-a.jpg',
                'fd',
                'm03/style.css',
                'm03/style.scss',
                'm03/style.css.map',
                'm03/style2.scss',
                'm03/style2.css.map',
                'm03/style2.css',
                'm04/m4u1/style.scss',
                'm04/m4u1/style.css',
                'm04/m4u1/style.css.map',
                'm04/m4u2/style.css',
                'm04/m4u2/style.scss',
                'm04/m4u2/style.css.map',
                'm04/m4u3/style.css',
                'm04/m4u3/style.scss',
                'm04/m4u3/style.css.map',
                'm04/m4u3/README.md',
                'm04/m4u1/',
                'm04/m4u2/',
                'm04/m4u3-copy',
                'z_r2-video-projekt',
                'up',
                'uploadR2.mjs',
                'P01-Banken database ER',
                'P01-Banken/style.scss',
                'P01-Banken/admin-style.css',
                'P01-Banken/style.css.map',
                'P01-Banken/style.css',
                'P01-Banken/README.md',
                'm06-db/style.scss',
                'm06-db/style.css',
                'm06-db/style.css.map',
                'm06-db/Er-Diagram.mmd',
                'm06-db/admin-style.css',
                'm06-db/users.json',
                'm06-db/transactions.json',
                'm06-db/db.sql',
                'm06-db/db_cnnct.php',
                'm06-db/error_log',
                'LMS',
                'LMS-T/data.sql',
                'LMS-T/data.sql',
//                'LMS-T/S.sql',
                'LMS-T/database.sql',
                'LMS-T/seed.sql',
                'LMS-T/style.css',
                'LMS-T/.css',
                'LMS-T/DOKUMENTATION.md',


        ]
]);

$content=$source->view();
?>
<!doctype html>
<html lang='en'>
<meta charset='utf-8' />
<title>View sourcecode</title>
<meta name="robots" content="noindex,noarchive,nofollow" />
<style>
    body { font-family: sans-serif; padding: 20px; }
    .src-breadcrumb { font-family: monospace; list-style-type:none; padding:0; margin:0 0 22px 0; }
    .src-breadcrumb li { display:inline; }
    .src-container { min-width:40em; margin-top: 20px; }
    .src-header { color:#000; border:1px solid #999; border-bottom:0; background:#eee; padding:0.5em; font-weight:bold; }
    .src-rows { float:left; text-align:right; color:#999; border:1px solid #999; border-right:0; background:#eee; padding:0.5em; }
    .src-rows a { text-decoration:none; color:inherit; }
    .src-code { white-space: pre; border:1px solid #999; background:#f9f9f9; padding:0.5em; overflow:auto; }

    /* Table Style */
    .file-table { width: 100%; border-collapse: collapse; margin-bottom: 1em; }
    .file-table th, .file-table td { border: 1px solid #ccc; padding: 0.5em; text-align: left; }
    .file-table th { background: #f0f0f0; position: sticky; top: 0; z-index: 1; }
    .file-table td a { text-decoration: none; color: #1d4ed8; display: inline-flex; align-items: center; gap: 8px; }
    .file-table td a .link-label { text-decoration: underline; text-decoration-color: transparent; transition: text-decoration-color 120ms ease; }
    .file-table td a:hover .link-label,
    .file-table td a:focus-visible .link-label { text-decoration-color: currentColor; }
    .file-table-wrap { overflow: auto; max-height: 70vh; border: 1px solid #e5e7eb; border-radius: 8px; }
    .file-table tbody tr:nth-child(even) { background: #fafafa; }
    .file-table tbody tr:hover { background: #eef2ff; box-shadow: inset 3px 0 0 #60a5fa; }
    .file-table td:first-child, .file-table th:first-child { width: 60%; }
    .file-table td:nth-child(2), .file-table th:nth-child(2) { width: 25%; }
    .file-table td:nth-child(3), .file-table th:nth-child(3) { width: 15%; text-align: right; }
    .file-table td:first-child { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .file-table tr.is-dir td:first-child a::before { content: "📁"; margin-right: 8px; display: inline-block; font-size: 18px; line-height: 1; transform: translateY(1px); transition: transform 120ms ease, filter 120ms ease; }
    .file-table tr.is-file td:first-child a::before { content: "📄"; margin-right: 8px; display: inline-block; font-size: 18px; line-height: 1; transform: translateY(1px); transition: transform 120ms ease, filter 120ms ease; }
    .file-table tbody tr:hover td:first-child a::before { transform: translateY(1px) scale(1.06); filter: saturate(1.05) contrast(1.05); }

    /* Footer  */
    .src-footer {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        width: 100%;
        background: #f3fce5;
        border-top: 1px solid #cce3a1;
        box-shadow: 0 -2px 8px rgba(0,0,0,0.04);
        font-size: 12px;
        color: #374151;
        text-align: center;
        padding: 10px 12px;
        z-index: 100;
    }
    .src-footer a { color: #1d4ed8; text-decoration: none; }
    .src-footer a:hover { text-decoration: underline; }
    body { padding-bottom: 56px; width: 80%; margin: auto;}

</style>
<body>
<h1>Visa Källkod</h1>
<p>The following files exist in this folder. Click to view.</p>
<?=$content?>
<footer class="src-footer">
    <a href="https://github.com/mosbth/csource" target="_blank" rel="noopener">CSource</a> &copy; 2010-2015 Mikael Roos.
</footer>
