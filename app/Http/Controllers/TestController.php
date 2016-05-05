<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Validator;
use App\Course;
use App\Seance;
use \Input;
use App\Test;
use App\File;
use Carbon\Carbon;

class TestController extends Controller
{

    protected $rules = [
        'course' => 'required',
        'seance' => 'required',
        'title' => 'required|max:255',
        'descr' => 'required'
        ];

    public function create( $id = null, $info = null ) {
        setlocale( LC_ALL, 'fr_FR');
    	$title = 'Créer une interrogation';
        $activePage = 'course';
    	$allCourses = Course::where( 'teacher_id', '=', \Auth::user()->id )->get();
        if( $allCourses->first() == null ) {
            return redirect()->back();
        }
    	if($id == null) {
            $firstCourse = $allCourses->first();
            $allSeances = Seance::where( 'course_id', '=', $firstCourse->id )->get();
    		return view('test/createTest', ['title' => $title, 'allCourses' => $allCourses, 'allSeances' => $allSeances, 'activePage' => $activePage]);
    	}
    	if($id != null) {

    		 if( $info == 'course' ) {
    		 	$course = Course::findOrFail( $id );
                $allSeances = Seance::where( 'course_id', '=', $id )->get();
    		 	return view('test/createTest', ['title' => $title, 'allCourses' => $allCourses, 'allSeances' => $allSeances, 'course' => $course, 'activePage' => $activePage]);
    		 }
    		 if( $info == 'seance' ) {
    		 	$seance = Seance::findOrFail( $id );
		    	$allSeances = Seance::where( 'course_id', '=', $seance->course_id )->get();
		        $course = Course::where( 'id', '=', $seance->course_id )->get();
		        return view('test/createTest', ['title' => $title, 'seance' => $seance, 'course'=> $course, 'allCourses' => $allCourses, 'allSeances' => $allSeances, 'activePage' => $activePage]);
    		 }
    	}
    }

    public function store() {
        $errors = Validator::make(Input::all(), $this->rules);
        if ($errors->fails()) {
            return Redirect()->back()->withErrors($errors);
        }

        $testFiles = [];

        if ( !empty( Input::file('file') ) ) {
            $files = Input::file('file');
            if( $files[0] !== null ) {
                $numberFiles = count( $files );
                for ($i = 0; $i < $numberFiles; $i++) {

                    $fileName = $files[$i]->getClientOriginalName();
                    $nameParts = explode('.', $fileName);
                    $ext = strtolower(end($nameParts));

                    if ( $ext === 'jpeg' OR $ext === 'gif' OR $ext === 'png' OR $ext === 'txt' OR $ext === 'pdf' OR $ext === 'docx' OR $ext === 'doc' ) {
                        // compléter la liste au fur et à mesure

                        $type = $ext;
                        $size = $files[$i]->getClientSize()/1000; // poid en Ko
                        $newname = md5( $fileName . time() ) . '.' . $ext;
                        $path = public_path('files/');

                        $file = File::create([
                            'title' => $fileName,
                            'filename' => $newname,
                            'type' => $type,
                            'size' => $size,
                            'from' => \Auth::user()->id
                        ]);

                        $files[$i]->move( $path, $newname);

                        $myFileID = File::where( 'filename', '=', $newname )->first()->id;
                        $testFiles[] = $myFileID;

                    }
                    else {
                        return Redirect()->back()->withErrors('Veuillez entrez un autre format de fichier');
                    }

                }
            }
        }

        $test = Test::create([
            'seance_id' => Input::get('seance'),
            'title' => Input::get('title'),
            'description' => Input::get('descr')
        ]);


        if( !empty( $testFiles ) ) {
            foreach( $testFiles as $testFileID ) {
                \DB::table('file_test')
                    ->insert(
                        array('file_id' => $testFileID, 'test_id' => $test->id)
                    );
            }
        }

        return redirect()->route('viewSeance', ['id' => Input::get('seance')]);
    }

    public function edit( $id ) {
        $test = Test::findOrFail( $id );
        $title = 'Modifier l’interrogation';
        $activePage = 'course';
        $allCourses = Course::where( 'teacher_id', '=', \Auth::user()->id )->get();
        $course = Seance::find($test->seance_id)->course;
        $allSeances = Seance::where( 'course_id', '=', $course->id )->get();
        return view('test/updateTest', compact('title', 'test', 'allSeances', 'allCourses', 'activePage'));
    }

    public function update( $id ) {
        $errors = Validator::make(Input::all(), $this->rules);
        if ($errors->fails()) {
            return Redirect()->back()->withErrors($errors);
        }

        $test = Test::findOrFail($id);
        if ( !empty( Input::file('file') ) ) {
            $files = Input::file('file');
            if( $files[0] !== null ) {
                $numberFiles = count( $files );
                for ($i = 0; $i < $numberFiles; $i++) {

                    $fileName = $files[$i]->getClientOriginalName();
                    $nameParts = explode('.', $fileName);
                    $ext = strtolower(end($nameParts));

                    if ( $ext === 'jpeg' OR $ext === 'gif' OR $ext === 'png' OR $ext === 'txt' OR $ext === 'pdf' OR $ext === 'docx' OR $ext === 'doc' ) {
                        // compléter la liste au fur et à mesure

                        $type = $ext;
                        $size = $files[$i]->getClientSize()/1000; // poid en Ko
                        $newname = md5( $fileName . time() ) . '.' . $ext;
                        $path = public_path('files/');

                        $file = File::create([
                            'title' => $fileName,
                            'filename' => $newname,
                            'type' => $type,
                            'size' => $size,
                            'from' => \Auth::user()->id
                        ]);

                        $files[$i]->move( $path, $newname);

                        $myFileID = File::where( 'filename', '=', $newname )->first()->id;
                        $testFiles[] = $myFileID;

                    }
                    else {
                        return Redirect()->back()->withErrors('Veuillez entrez un autre format de fichier');
                    }

                }
            }
        }


        if( !empty( $testFiles ) ) {
            foreach( $testFiles as $testFileID ) {
                \DB::table('file_test')
                    ->insert(
                        array('file_id' => $testFileID, 'test_id' => $test->id)
                    );
            }
        }
        $test->title = Input::get('title');
        $test->description = Input::get('descr');
        $test->updated_at = Carbon::now();
        $test->save();
        return redirect()->route('viewSeance', ['id' => $test->seance->id]);
    }

    public function delete( $id ) {
        $test = Test::findOrFail( $id );
        $test->delete();
        return redirect()->back();
    }

    public function deleteFile( $id_file, $id_test ) {
        $test = \DB::table('file_test')->where('file_id', '=', $id_file)->where('test_id', '=', $id_test)->delete();
        return redirect()->back();
    }
}
