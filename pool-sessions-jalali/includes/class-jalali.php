<?php
namespace PoolSessionsJalali;

/**
 * Jalali Calendar Conversion Class
 * 
 * Provides methods to convert between Gregorian and Jalali calendars
 * with proper leap year handling and month length calculations
 */
class Jalali {
    
    /**
     * Convert Jalali date to Gregorian date
     * 
     * @param int $jy Jalali year
     * @param int $jm Jalali month
     * @param int $jd Jalali day
     * @return array Array with 'year', 'month', 'day' keys
     */
    public function jalali_to_gregorian($jy, $jm, $jd) {
        $gy = $jy + 621;
        
        if ($jm <= 6) {
            $days = ($jm - 1) * 31 + $jd;
        } else {
            $days = 186 + ($jm - 7) * 30 + $jd;
        }
        
        if ($jy > 979) {
            $days += 365;
            $gy++;
        }
        
        $gy += 4 * floor(($jy - 1) / 33);
        $gy += floor(($jy - 1) % 33 / 4);
        
        if ($jy % 33 == 1 && $jy > 979) {
            $days++;
        }
        
        $gy += floor($days / 365);
        $days = $days % 365;
        
        if ($days < 186) {
            $gm = 1 + floor($days / 31);
            $gd = ($days % 31) + 1;
        } else {
            $days -= 186;
            $gm = 7 + floor($days / 30);
            $gd = ($days % 30) + 1;
        }
        
        return array(
            'year' => $gy,
            'month' => $gm,
            'day' => $gd
        );
    }
    
    /**
     * Convert Gregorian date to Jalali date
     * 
     * @param int $gy Gregorian year
     * @param int $gm Gregorian month
     * @param int $gd Gregorian day
     * @return array Array with 'year', 'month', 'day' keys
     */
    public function gregorian_to_jalali($gy, $gm, $gd) {
        $gy -= 621;
        
        if ($gm <= 2) {
            $days = 365 * $gy + floor($gy / 4) - floor($gy / 100) + floor($gy / 400) + 31 * ($gm - 1) + $gd;
        } else {
            $days = 365 * $gy + floor($gy / 4) - floor($gy / 100) + floor($gy / 400) + 31 * ($gm - 1) + $gd + floor(0.6 * ($gm + 1));
        }
        
        $jy = floor($days / 365.25);
        $days = $days % 365.25;
        
        if ($days < 186) {
            $jm = 1 + floor($days / 31);
            $jd = ($days % 31) + 1;
        } else {
            $days -= 186;
            $jm = 7 + floor($days / 30);
            $jd = ($days % 30) + 1;
        }
        
        return array(
            'year' => $jy,
            'month' => $jm,
            'day' => $jd
        );
    }
    
    /**
     * Get the number of days in a Jalali month
     * 
     * @param int $jy Jalali year
     * @param int $jm Jalali month
     * @return int Number of days in the month
     */
    public function jalali_month_length($jy, $jm) {
        if ($jm <= 6) {
            return 31;
        } elseif ($jm <= 11) {
            return 30;
        } else {
            // Esfand (12th month)
            return $this->is_jalali_leap_year($jy) ? 30 : 29;
        }
    }
    
    /**
     * Check if a Jalali year is a leap year
     * 
     * @param int $jy Jalali year
     * @return bool True if leap year
     */
    public function is_jalali_leap_year($jy) {
        $leap_years = array(1, 5, 9, 13, 17, 22, 26, 30);
        return in_array(($jy % 33), $leap_years);
    }
    
    /**
     * Get Jalali month names
     * 
     * @return array Array of month names
     */
    public function get_month_names() {
        return array(
            1 => 'فروردین',
            2 => 'اردیبهشت',
            3 => 'خرداد',
            4 => 'تیر',
            5 => 'مرداد',
            6 => 'شهریور',
            7 => 'مهر',
            8 => 'آبان',
            9 => 'آذر',
            10 => 'دی',
            11 => 'بهمن',
            12 => 'اسفند'
        );
    }
    
    /**
     * Get Jalali weekday names
     * 
     * @return array Array of weekday names
     */
    public function get_weekday_names() {
        return array(
            0 => 'شنبه',
            1 => 'یکشنبه',
            2 => 'دوشنبه',
            3 => 'سه‌شنبه',
            4 => 'چهارشنبه',
            5 => 'پنج‌شنبه',
            6 => 'جمعه'
        );
    }
    
    /**
     * Get the first day of a Jalali month as weekday
     * 
     * @param int $jy Jalali year
     * @param int $jm Jalali month
     * @return int Weekday (0 = Saturday, 6 = Friday)
     */
    public function get_first_day_of_month($jy, $jm) {
        $gregorian = $this->jalali_to_gregorian($jy, $jm, 1);
        $timestamp = mktime(0, 0, 0, $gregorian['month'], $gregorian['day'], $gregorian['year']);
        $weekday = date('w', $timestamp);
        
        // Convert to Jalali weekday (Saturday = 0)
        return ($weekday + 1) % 7;
    }
}
