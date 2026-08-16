<div class="row">
    <div class="col-md-3">
        {!! Form::label('name', 'Name*', ['class' => 'col-form-label']) !!}
        {!! Form::text('name', null ,['class' => 'form-control ', 'placeholder' => '', 'autocomplete' => 'off']) !!}
    </div>

    <div class="col-md-2">
        {!! Form::label('dob', 'DOB*', ['class' => 'col-form-label']) !!}
        {!! Form::date('dob', null ,['class' => 'form-control ', 'placeholder' => '', 'autocomplete' => 'off']) !!}
    </div>

    <div class="col-md-2">
        {!! Form::label('height', 'Height*', ['class' => 'col-form-label']) !!}
        {!! Form::text('height', null ,['class' => 'form-control ', 'placeholder' => '', 'autocomplete' => 'off']) !!}
    </div>

    <div class="col-md-2">
        {!! Form::label('weight', 'Weight*', ['class' => 'col-form-label']) !!}
        {!! Form::text('weight', null ,['class' => 'form-control ', 'placeholder' => '', 'autocomplete' => 'off']) !!}
    </div>

    <div class="col-md-2">
        {!! Form::label('marital_status', 'Marital Status*', ['class' => 'col-form-label']) !!}
        {!! Form::select('marital_status', ['Never Married'=>'Never Married','Widowed'=>'Widowed','Divorced'=>'Divorced','Awaiting Divorce'=>'Awaiting Divorce'] , null, ['class'=>'form-control select2','placeholder'=>'-- Marital Status --']) !!}
    </div>

    <div class="col-md-2">
        {!! Form::label('body_type', 'Body Type', ['class' => 'col-form-label']) !!}
        {!! Form::select('body_type', ['Never Married'=>'Never Married','Widowed'=>'Widowed','Divorced'=>'Divorced','Awaiting Divorce'=>'Awaiting Divorce'] , null, ['class'=>'form-control select2','placeholder'=>'-- Marital Status --']) !!}
    </div>

    <div class="col-md-2">
        {!! Form::label('physical_status', 'Physical Status', ['class' => 'col-form-label']) !!}
        {!! Form::select('physical_status', ['Normal'=>'Normal','Physically Challenged'=>'Physically Challenged'] , null, ['class'=>'form-control select2','placeholder'=>'-- Physical Status --']) !!}
    </div>

    <div class="col-md-2">
        {!! Form::label('mother_tongue', 'Mother Tongue', ['class' => 'col-form-label']) !!}
        {!! Form::select('mother_tongue', ['Telugu'=>'Telugu'] , null, ['class'=>'form-control select2','placeholder'=>'-- Mother Tongue --']) !!}
    </div>

    <div class="col-md-2">
        {!! Form::label('eating_habits', 'Eating Habits', ['class' => 'col-form-label']) !!}
        {!! Form::select('eating_habits', ['Vegetarian'=>'Vegetarian','Non Vegetarian'=>'Non Vegetarian','Eggetarian'=>'Eggetarian'] , null, ['class'=>'form-control select2','placeholder'=>'-- Eating Habits --']) !!}
    </div>

    <div class="col-md-2">
        {!! Form::label('drinking_habits', 'Drinking Habits', ['class' => 'col-form-label']) !!}
        {!! Form::select('drinking_habits', ['No'=>'No','Drinks Socially'=>'Drinks Socially','Yes'=>'Yes'] , null, ['class'=>'form-control select2','placeholder'=>'-- Eating Habits --']) !!}
    </div>

    <div class="col-md-2">
        {!! Form::label('smoking_habits	', 'Smoking Habits', ['class' => 'col-form-label']) !!}
        {!! Form::select('smoking_habits	', ['No'=>'No','Occasionally'=>'Occasionally','Yes'=>'Yes'] , null, ['class'=>'form-control select2','placeholder'=>'-- Eating Habits --']) !!}
    </div>

    <div class="col-md-2">
        {!! Form::label('religion', 'Religion', ['class' => 'col-form-label']) !!}
        {!! Form::select('religion', ['Hindu'=>'Hindu'] , null, ['class'=>'form-control select2','placeholder'=>'-- Religion --']) !!}
    </div>

    <div class="col-md-2">
        {!! Form::label('caste', 'Caste', ['class' => 'col-form-label']) !!}
        {!! Form::select('caste', ['Balija'=>'Balija'] , null, ['class'=>'form-control select2','placeholder'=>'-- Caste --']) !!}
    </div>

    <div class="col-md-2">
        {!! Form::label('sub_caste', 'Sub Caste', ['class' => 'col-form-label']) !!}
        {!! Form::select('sub_caste', ['Balija'=>'Balija'] , null, ['class'=>'form-control select2','placeholder'=>'-- Sub Caste --']) !!}
    </div>

    <div class="col-md-2">
        {!! Form::label('gothram', 'Gothram*', ['class' => 'col-form-label']) !!}
        {!! Form::text('gothram', null ,['class' => 'form-control ', 'placeholder' => '', 'autocomplete' => 'off']) !!}
    </div>

    <div class="col-md-2">
        {!! Form::label('raasi', 'Raasi', ['class' => 'col-form-label']) !!}
        {!! Form::select('raasi', array('Mesa' => 'Mesa', 'Vrushabham' => 'Vrushabham'), NULL, [ 'class' => 'form-control', 'autocomplete' => 'off' ]) !!}
    </div>

    <div class="col-md-2">
        {!! Form::label('star', 'Star', ['class' => 'col-form-label']) !!}
        {!! Form::select('star', array('Aries' => 'Aries', 'Gemini' => 'Gemini'), NULL, [ 'class' => 'form-control', 'autocomplete' => 'off' ]) !!}
    </div>

    <div class="col-md-2">
        {!! Form::label('padam', 'Padam', ['class' => 'col-form-label']) !!}
        {!! Form::select('padam', array('1 st' => '1 st', '2 nd' => '2 nd','3 rd'=>'3 rd','4 th'=>'4 th'), NULL, [ 'class' => 'form-control', 'autocomplete' => 'off' ]) !!}
    </div>

    <div class="col-md-2">
        {!! Form::label('dosham', 'Dosham', ['class' => 'col-form-label']) !!}
        {!! Form::select('dosham', ['No'=>'No','Don’t Know'=>'Occasionally','Yes'=>'Yes'], NULL, [ 'class' => 'form-control', 'autocomplete' => 'off' ]) !!}
    </div>

    <div class="col-md-2">
        {!! Form::label('country', 'Country', ['class' => 'col-form-label']) !!}
        {!! Form::select('country', ['India'=>'India'], NULL, [ 'class' => 'form-control', 'autocomplete' => 'off' ]) !!}
    </div>

    <div class="col-md-2">
        {!! Form::label('state', 'State', ['class' => 'col-form-label']) !!}
        {!! Form::select('state', ['AP'=>'AP'], NULL, [ 'class' => 'form-control', 'autocomplete' => 'off' ]) !!}
    </div>


    <div class="col-md-2">
        {!! Form::label('time_of_birth', 'Time of birth', ['class' => 'col-form-label']) !!}
        {!! Form::text('time_of_birth', null ,['class' => 'form-control ', 'placeholder' => '', 'autocomplete' => 'off']) !!}
    </div>


    <!-- Education -->
    <div class="col-md-2">
        {!! Form::label('education_details', 'Education Details', ['class' => 'col-form-label']) !!}
        {!! Form::text('education_details', null ,['class' => 'form-control ', 'placeholder' => '', 'autocomplete' => 'off']) !!}
    </div>
    <div class="col-md-2">
        {!! Form::label('citizenship', 'Citizenship', ['class' => 'col-form-label']) !!}
        {!! Form::text('citizenship', null ,['class' => 'form-control ', 'placeholder' => '', 'autocomplete' => 'off']) !!}
    </div>
    <!-- Education -->

    <div class="col-md-2">
        {!! Form::label('status', 'Status*', ['class' => 'col-form-label']) !!}
        {!! Form::select('status', array('1' => 'Enable', '2' => 'Disable'), NULL, [ 'class' => 'form-control', 'autocomplete' => 'off' ]) !!}
    </div>

</div>
