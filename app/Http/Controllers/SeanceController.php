<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use \Input;
use App\Course;
use App\Seance;
use App\Work;
use App\Test;
use Carbon\Carbon;

class SeanceController extends Controller
{

    public function create( $id ) {
        $title = 'Créer une séance';
        $courses = Course::where( 'teacher_id', '=', \Auth::user()->id )->get();
        $days = [
            "monday" => "lundi",
            "tuesday" => "mardi",
            "wednesday" => "mercredi",
            "thursday" => "jeudi",
            "friday" => "vendredi",
            "saturday" => "samedi",
            "sunday" => "dimanche"
        ];
        return view('seance/createSeance', compact('title', 'id', 'courses', 'days'));
    }

    public function store() {
        $day = Input::get('date').' '.Input::get('daypicker');
        $start_hours = Input::get('date').' '.Input::get('start_hours');
        $end_hours = Input::get('date').' '.Input::get('end_hours');
        $dayFr = [ "monday" => "lundi",
                   "tuesday" => "mardi", 
                   "wednesday" => "mercredi", 
                   "thursday" => "jeudi", 
                   "friday" => "vendredi", 
                   "saturday" => "samedi", 
                   "sunday" => "dimanche" ];

        $obj_dateStart = date_create($_POST['start_date']  . ' -1 day');
        $obj_dateEnd = date_create($_POST['end_date']);
        while ($obj_dateStart->format('U') <= $obj_dateEnd->format('U')) {
            $obj_dateStart->modify('next '.$day);
            if ($obj_dateStart->format('U') <= $obj_dateEnd->format('U') && $obj_dateStart >= Carbon::today()) {
                //echo 'ce '.$dayFr[ $day ]." " . $obj_dateStart->format('d-m-Y') .'<br/>';
                //echo $obj_dateStart->format('d-m-Y') .'<br/>';
                $seance = Seance::create([
                    'course_id' => Input::get('course'),
                    'start_hours' => $obj_dateStart->format('Y-m-d').$start_hours.':00',
                    'end_hours' => $obj_dateEnd->format('Y-m-d').$end_hours.':00'
                ]);
            }
        }

        return redirect()->route('viewCourse', ['id' => Input::get('course'), 'action' => 1]);
    }


    public function edit( $id ) {
        $seance = Seance::findOrFail($id);
        $days = [
            "monday" => "lundi",
            "tuesday" => "mardi",
            "wednesday" => "mercredi",
            "thursday" => "jeudi",
            "friday" => "vendredi",
            "saturday" => "samedi",
            "sunday" => "dimanche"
        ];
        $title = 'Modifier la séance';
        $courses = Course::where( 'teacher_id', '=', \Auth::user()->id )->get();
        $course_id = $seance->course_id;
        $start_day = substr($seance->start_hours, 0, 10);
        $end_day = substr($seance->end_hours, 0, 10);
        $start_hours = substr($seance->start_hours, 11, 5);
        $end_hours = substr($seance->end_hours, 11, 5);
        return view('seance/updateSeance', compact('title', 'seance', 'days', 'id', 'courses', 'course_id', 'start_day', 'end_day', 'start_hours', 'end_hours'));
    }

    public function update( $id ) {
        $seance = Seance::findOrFail($id);
        $day = Input::get('date');
        $start_hours = Input::get('start_hours');
        $end_hours = Input::get('end_hours');
        $seance->course_id = Input::get('course');
        $seance->start_hours = $day.$start_hours.':00';
        $seance->end_hours = $day.$end_hours.':00';

        $seance->save();
        return redirect()->route('viewCourse', ['id' => Input::get('course'), 'action' => 1]);
    }

    public function view( $id ) {
        setlocale( LC_ALL, 'fr_FR');
        $seance = Seance::findOrFail($id);
        $works = Work::where('seance_id', '=', $id)->get();
        $tests = Test::where('seance_id', '=', $id)->get();
        $title = 'Séance du '.$seance->start_hours->formatLocalized('%A %d %B %Y') . ' de ' . $seance->start_hours->formatLocalized('%Hh%M') . ' à ' . $seance->end_hours->formatLocalized('%Hh%M');
        return view('seance/viewSeance', compact( 'title', 'id', 'seance', 'works', 'tests' ));
    }

    public function getByCourse( $id_course ) {
        return Course::find($id_course)->seances;
    }

    public function delete( $id, $course ) {
        $seance = Seance::findOrFail($id);

        $works = Work::where( 'seance_id', '=', $id )->get();
        foreach ($works as $work) {
            $work->delete();   
        }

        $tests = Test::where( 'seance_id', '=', $id )->get();
        foreach ($tests as $test) {
            $test->delete();   
        }
        $seance->delete();
        return redirect()->route('viewCourse', ['id' => $course, 'action' => 1]);
    }

    public function deleteAll( $course ) {
        $seances = Seance::where( 'course_id', '=', $course )->get();
        foreach ($seances as $seance) {
            $works = Work::where( 'seance_id', '=', $seance->id )->get();
            foreach ($works as $work) {
                $work->delete();   
            }

            $tests = Test::where( 'seance_id', '=', $seance->id )->get();
            foreach ($tests as $test) {
                $test->delete();   
            }

            $seance->delete();   
        }
        return redirect()->route('viewCourse', ['id' => $course, 'action' => 1]);
    }
}
