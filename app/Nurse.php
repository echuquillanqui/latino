<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Nurse extends Model
{
  public static function requiredTreatmentFields()
  {
      $fields = [];
      for ($row = 1; $row <= 8; $row++) {
          $suffix = $row === 1 ? '' : $row;
          $fields[] = 'hr' . $suffix;
          $fields[] = 'pa' . $suffix;
          $fields[] = $row === 1 ? 'fc1' : 'fc' . $row;
          $fields[] = 'qb' . $suffix;
          $fields[] = 'cnd' . $suffix;
          $fields[] = 'ra' . $suffix;
          $fields[] = 'rv' . $suffix;
          $fields[] = 'ptm' . $suffix;
      }

      return $fields;
  }

  public static function clinicalNurseFields()
  {
      $fields = array_merge(self::requiredTreatmentFields(), [
          'start_pa', 'end_pa', 'end_weight', 'machine', 'brand_model', 'position', 'filter',
          'access_arterial', 'access_venoso', 'iron', 'epo2000', 'epo4000', 'hidroxi',
          'calcitriol', 'others_med', 'end_observation', 's', 'o', 'a', 'p',
      ]);

      for ($row = 1; $row <= 8; $row++) {
          $suffix = $row === 1 ? '' : $row;
          $fields[] = 'sol_hemodev' . $suffix;
          $fields[] = 'obs' . $suffix;
      }

      return $fields;
  }

  public static function clinicalMedicalFields()
  {
      // hour_hd, patient, room, shift and date_order are supplied by the order, so they do not mean the medical form was filled.
      return [
          'heparin', 'dry_weight', 'start_weight', 'uf', 'qb', 'qd', 'bicarbonat', 'cnd',
          'start_na', 'end_na', 'profile_na', 'profile_uf', 'area_filter', 'membrane',
          'clinical_trouble', 'fc', 'evaluation', 'end_evaluation', 'indications',
          'start_hour', 'end_hour', 'signal', 'epo', 'epo4000', 'iron', 'vitb12', 'calci',
          'so2', 'fio', 'temp',
      ];
  }

  public function hasClinicalData()
  {
      foreach (self::clinicalNurseFields() as $field) {
          $value = $this->getAttribute($field);
          if ($value !== null && trim((string) $value) !== '' && trim((string) $value) !== '-') {
              return true;
          }
      }

      $order = $this->relationLoaded('order') ? $this->getRelation('order') : $this->order;
      $medical = $order ? ($order->relationLoaded('medical') ? $order->getRelation('medical') : $order->medical) : null;
      if ($medical) {
          foreach (self::clinicalMedicalFields() as $field) {
              $value = $medical->getAttribute($field);
              if ($value !== null && trim((string) $value) !== '' && trim((string) $value) !== '-') {
                  return true;
              }
          }
      }

      return false;
  }

  public function isTreatmentComplete()
  {
      if (!$this->user_id || !$this->user_id2) {
          return false;
      }

      foreach (self::requiredTreatmentFields() as $field) {
          if ($this->getAttribute($field) === null || trim((string) $this->getAttribute($field)) === '') {
              return false;
          }
      }

      return true;
  }

  public function scopeComplete($query)
  {
      $query->whereNotNull('nurses.user_id')->whereNotNull('nurses.user_id2');
      foreach (self::requiredTreatmentFields() as $field) {
          $query->whereNotNull('nurses.' . $field)->where('nurses.' . $field, '<>', '');
      }

      return $query;
  }

  public function scopeIncomplete($query)
  {
      return $query->where(function ($incomplete) {
          $incomplete->whereNull('nurses.user_id')->orWhereNull('nurses.user_id2');
          foreach (self::requiredTreatmentFields() as $field) {
              $incomplete->orWhereNull('nurses.' . $field)->orWhere('nurses.' . $field, '=', '');
          }
      });
  }

  public function scopePartial($query)
  {
      return $query->incomplete()->where(function ($partial) {
          foreach (self::clinicalNurseFields() as $field) {
              $partial->orWhere(function ($hasData) use ($field) {
                  $hasData->whereNotNull('nurses.' . $field)
                      ->where('nurses.' . $field, '<>', '')
                      ->where('nurses.' . $field, '<>', '-');
              });
          }

          $partial->orWhereHas('order.medical', function ($medicalQuery) {
              $medicalQuery->where(function ($hasMedicalData) {
                  foreach (self::clinicalMedicalFields() as $field) {
                      $hasMedicalData->orWhere(function ($hasValue) use ($field) {
                          $hasValue->whereNotNull('medicals.' . $field)
                              ->where('medicals.' . $field, '<>', '')
                              ->where('medicals.' . $field, '<>', '-');
                      });
                  }
              });
          });
      });
  }

  public function scopeWithoutClinicalData($query)
  {
      foreach (self::clinicalNurseFields() as $field) {
          $query->where(function ($empty) use ($field) {
              $empty->whereNull('nurses.' . $field)
                  ->orWhere('nurses.' . $field, '')
                  ->orWhere('nurses.' . $field, '-');
          });
      }

      return $query->whereDoesntHave('order.medical', function ($medicalQuery) {
          $medicalQuery->where(function ($hasMedicalData) {
              foreach (self::clinicalMedicalFields() as $field) {
                  $hasMedicalData->orWhere(function ($hasValue) use ($field) {
                      $hasValue->whereNotNull('medicals.' . $field)
                          ->where('medicals.' . $field, '<>', '')
                          ->where('medicals.' . $field, '<>', '-');
                  });
              }
          });
      });
  }

  protected $fillable = [
     'order_id',
     'patient',
     'room',
     'shift',
     'hcl',
     'frequency',
     'nhd',
     'others',
     'start_pa',
     'end_pa',
     'start_weight',
     'end_weight',
     'machine',
     'brand_model',
     'position',
     'filter',
     'uf',
     'access_arterial',
     'access_venoso',
     'iron',
     'epo2000',
     'epo4000',
     'hidroxi',
     'calcitriol',
     'others_med',
     'end_observation',
     'aspect_dializer',
     'date_order',

      's',
      'o',
      'a',
      'p',

     'hr',
     'pa',
     'fc1',
     'qb',
     'cnd',
     'ra',
     'rv',
     'ptm',
      'sol_hemodev',
     'obs',

     'hr2',
     'pa2',
     'fc2',
     'qb2',
     'cnd2',
     'ra2',
     'rv2',
     'ptm2',
      'sol_hemodev2',
     'obs2',

     'hr3',
     'pa3',
     'fc3',
     'qb3',
     'cnd3',
     'ra3',
     'rv3',
     'ptm3',
      'sol_hemodev3',
     'obs3',

     'hr4',
     'pa4',
     'fc4',
     'qb4',
     'cnd4',
     'ra4',
     'rv4',
     'ptm4',
      'sol_hemodev4',
     'obs4',

     'hr5',
     'pa5',
     'fc5',
     'qb5',
     'cnd5',
     'ra5',
     'rv5',
     'ptm5',
      'sol_hemodev5',
     'obs5',

     'hr6',
     'pa6',
     'fc6',
     'qb6',
     'cnd6',
     'ra6',
     'rv6',
     'ptm6',
      'sol_hemodev6',
     'obs6',

     'hr7',
     'pa7',
     'fc7',
     'qb7',
     'cnd7',
     'ra7',
     'rv7',
     'ptm7',
      'sol_hemodev7',
     'obs7',

      'hr8',
      'pa8',
      'fc8',
      'qb8',
      'cnd8',
      'ra8',
      'rv8',
      'ptm8',
      'sol_hemodev8',
      'obs8',
      'user_id',
	  'user_id2'
];


     public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

	public function user2()
    {
        return $this->belongsTo(User::class, 'user_id2');
    }

    public function scopePatient($query, $patient)
    {
        if($patient)
            return $query->where('patient', 'LIKE', $patient . '%');
    }

    public function scopeRoom($query, $room)
    {
        if($room)
            return $query->whereHas('order.room', function ($rooms) use ($room) {
                $rooms->where('name', $room);
            });
    }

    public function scopeShift($query, $shift)
    {
        if($shift)
            return $query->where('shift', $shift);
    }

    public function scopeDate_order($query, $date_order)
    {
        if($date_order)
            return $query->where('date_order', $date_order);
    }

}
