<?php
/**
 * Created by PhpStorm.
 * User: NG
 * Date: 2/27/2019
 * Time: 1:42 AM
 */

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use function MongoDB\BSON\toJSON;

class MdFileController extends Controller
{

    public function addEmployeeName(Request $request) {

    }

    public static function getSingleFileByPath ( $path ) {
        if (Storage::disk('local')->exists($path)) {
            try {
                $text =  Storage::disk('local')->get($path);
                return $text;
            } catch (\Exception $exception){
                return json_encode($exception);
            }
        } ;
        return 'File Path Not Found';
    }

    public function getFilesOfaMonth(Request $request) {
        $data = $request->all();
        $month = $data['month'];
        $year = $data['year'];
        $root_dir = 'workflow_timeline';
        //$year = '2019';
        $FOLDER_NAME = "$root_dir/$year/$month";
        $sub_directories = Storage::disk('local')->directories("$FOLDER_NAME");
        $appenedAllMonthText = '';
        foreach ($sub_directories as $directory) {
            $output = self::getFilesOfaDay($directory);
            if(empty($appenedAllMonthText)) {
                $appenedAllMonthText = $output;
            } else {
                $appenedAllMonthText = $appenedAllMonthText."<br>".$output;
            }
        }
        $textFormat = "<h2 style='font-weight: 400; font-style: italic'>$month $year</h2> <div class='ml-5'>$appenedAllMonthText</div>";
        return $textFormat;
    }

    public static function getFilesOfaDay($folder_name) {

        $FOLDER_NAME = $folder_name;
        $allfilepaths = Storage::disk('local')->files("$FOLDER_NAME");

        if (empty($allfilepaths)) {
            return 'All File Paths Are Empty';
        }

        $appendedFilesText = '';
        foreach ($allfilepaths as $filepath) {
            $output = self::getSingleFileByPath($filepath);

            $directories =  explode('/', $filepath);
            $directoriesDepth = sizeof($directories);

            $fileNameWithExtension = $directories[$directoriesDepth-1];
            for($i= strlen( $fileNameWithExtension) ; $i>=0 ; $i--) {
                $char = $fileNameWithExtension[$i-1];
                if($char === '.') {
                    $extensionPosition = $i;
                    break;
                }
            }

            $onlyFileName = substr($fileNameWithExtension, 0, $extensionPosition-1);
            $dayName = $directories[$directoriesDepth-2];
            $monthName = $directories[$directoriesDepth-3];
            $extensionPosition = 0;

            $detailsOutput = "<details><summary class='ml-3'>".$monthName." $dayName, ".$onlyFileName."</summary>\n<div class='ml-3'>$output</div>\n</details>";
            if (empty($appendedFilesText)) {
                $appendedFilesText = $detailsOutput;
            } else {
                $appendedFilesText = $appendedFilesText."<br>$detailsOutput";
            }
        }
        return "<details><summary class='ml-3'>".$monthName." $dayName"."</summary>\n<div class='ml-5'>$appendedFilesText</div>\n</details>";
        //return  $appendedFilesText;
    }



    public function savemdfile(Request $request) {
        // dd( date('Y/M/d'), date('h:i:s') );
        // workflow_timeline/2019/Feb/27/Awon.md
        // return 'ddddddddddd';

        $userName = $request->username;
        $textData = $request->textData;
        // dd($textData);
        // return $textData;
        // dd($textData);
        $textDataTemplate = "<details><summary class='ml-3'>".date('h:i:s')."</summary>\n<pre class='ml-3'>$textData</pre>\n</details>";
        // return $textData;
        // dd('comessssssssss');
        // $userName = "Awon";
        $FOLDER_NAME = "workflow_timeline/".date('Y/M/d');
        $FILE_NAME = $userName;
        $FILE_EXTENSION = '.md';

        // $textData = "# Pulp Fiction \n## Say What Again \n### I dare you \n#### I double dare you";

        //$fileHeading = "<h4 style='font-weight: 400;'> $userName: ".date('M d, Y')."</h4>";
        $fileHeading = '';
        $fileBodyPart = $textDataTemplate;
        // return $fileBodyPart;
        $OUTPUT_FIRSTLINE_WITH_FILE_HEADING = "$fileHeading"."$fileBodyPart";

        if(Storage::disk('local')->exists("$FOLDER_NAME")) {
            $allfilepaths = Storage::disk('local')->files("$FOLDER_NAME");
            // NOTE:: If allfilepaths are not empty ai checking ta dite hobe.
            if (empty($allfilepaths)) {
                $newFileName = "$FILE_NAME"."$FILE_EXTENSION";
                Storage::disk('local')->put("$FOLDER_NAME/$newFileName", $OUTPUT_FIRSTLINE_WITH_FILE_HEADING);
                return 'New Log Saved';
            }

            $toEditFilePath = "$FOLDER_NAME"."/"."$FILE_NAME"."$FILE_EXTENSION";
            $fileExists =  Storage::disk('local')->exists($toEditFilePath);
            if ($fileExists) {
                Storage::disk('local')->append("$toEditFilePath", "\n".$fileBodyPart);
                return 'Log Saved';
            }
            Storage::disk('local')->put("$toEditFilePath", $OUTPUT_FIRSTLINE_WITH_FILE_HEADING);
            return 'New Log Saved';
        } else {
            $newFileName = "$FILE_NAME"."$FILE_EXTENSION";
            Storage::disk('local')->put("$FOLDER_NAME/$newFileName", $OUTPUT_FIRSTLINE_WITH_FILE_HEADING);
            return 'New Log Saved';
        }
    }





    public static function saveSearchLog($dataArray) {

        $PER_FILE_LINE = 5; // Must be greater than equal 3;
        $FOLDER_NAME = 'searchlog';
        $FILE_NAME = 'user_search_log';
        $FILE_EXTENSION = '.txt';

        //LogLine: n | user_id | user_name | date | searchstring
        /*
        $user = Auth::user();
        $user_id = Auth::id();
        $userName = $user->name;
        $email = $user->email;
        $mobile = $user->mobile;
        */
        $user_id = 1; // hardcoded dummy
        $userName = 'Md. Dummy'; // hardcoded dummy

        $reqInString = http_build_query($dataArray);
        $searchlogData = $reqInString;

        $date_time  = date("Y/m/d H:i:s");
        $fileHeading = "N|user_id|user_name|date_time|search_string";
        $OUTPUT_FIRSTLINE_WITH_FILE_HEADING = "$fileHeading"."\n"."0|$user_id|$userName|$date_time|$searchlogData";

        if(Storage::disk('local')->exists("$FOLDER_NAME")) {
            $allfilepaths = Storage::disk('local')->files("$FOLDER_NAME");
            // NOTE:: If allfilepaths are not empty ai checking ta dite hobe.
            if (empty($allfilepaths)) {
                // $newFileName = "user_search_log_0.txt";
                $newFileName = "$FILE_NAME"."_0"."$FILE_EXTENSION";
                Storage::disk('local')->put("$FOLDER_NAME/$newFileName", $OUTPUT_FIRSTLINE_WITH_FILE_HEADING);
                return 'Log Saved';
            }
            $toEditFilePath = $allfilepaths[0];
            $toEditFileLastModifiedTime = Storage::lastModified($toEditFilePath);
            $fileNumber = 0;
            foreach ($allfilepaths as $filepath) {
                if ($toEditFileLastModifiedTime <= Storage::lastModified($filepath)) {
                    $toEditFilePath = $filepath;
                    $toEditFileLastModifiedTime = Storage::lastModified($filepath);
                    $fileNumber++;
                }
            }
            $file = Storage::get($toEditFilePath);
            $lineno = substr_count($file, "\n");

            if ($lineno > $PER_FILE_LINE - 2 ) { /* CREATE NEW FILE AND INSERT */
                // $newFileName = "user_search_log_$fileNumber.txt";
                $newFileName = "$FILE_NAME"."_$fileNumber"."$FILE_EXTENSION";
                Storage::disk('local')->put("$FOLDER_NAME/$newFileName", $OUTPUT_FIRSTLINE_WITH_FILE_HEADING);
            } else { /* APPEND */

                $OUTPUT_BODY = "$lineno|$user_id|$userName|$date_time|$searchlogData"; // HAS USE HERE???
                Storage::disk('local')->append("$toEditFilePath", $OUTPUT_BODY);
            }
            return 'Log Saved';
        } else {
            $output = $OUTPUT_FIRSTLINE_WITH_FILE_HEADING;
            // $newFileName = "user_search_log_0.txt";
            $newFileName = "$FILE_NAME"."_0"."$FILE_EXTENSION";
            Storage::disk('local')->put("$FOLDER_NAME/$newFileName", $output);
            return 'Log Saved';
        }
    }

    public function unused_now_getFilesOfaDay_backup() { // Not Used Now
        $FOLDER_NAME = "workflow_timeline/".date('Y/M/d');
        /*
            $userName = "Tanay";
            $FILE_NAME = $userName;
            $FILE_EXTENSION = '.md';
            $filePath = "$FILE_NAME"."$FILE_EXTENSION";
        */
        $allfilepaths = Storage::disk('local')->files("$FOLDER_NAME");

        if (empty($allfilepaths)) {
            return 'All File Paths Are Empty';
        }

        $appendedFilesText = '';
        foreach ($allfilepaths as $filepath) {
            $output = self::getSingleFileByPath($filepath);
            if (empty($appendedFilesText)) {
                $appendedFilesText = $output;
            } else {
                $appendedFilesText = $appendedFilesText."<br>$output";
            }
        }

        return $appendedFilesText;
    }
}
