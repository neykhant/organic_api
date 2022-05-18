<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function getSaleReport()
    {
        $user = Auth::user();
        $shop_id = $user->shop_id;

        if($shop_id == 0){
            $totalInvoice = new \App\Models\Invoice;
            if (request()->start_date && request()->end_date) {
                $totalInvoice = $totalInvoice->whereBetween('created_at', [request()->start_date, request()->end_date]);
            }
            $totalInvoice = $totalInvoice->select(DB::raw('sum(final_total) as final_total'), DB::raw('sum(item_buy_total) as item_buy_total'), DB::raw('sum(item_total) as item_total'), DB::raw('sum(service_total) as service_total'));
            $totalInvoice = $totalInvoice->get();
    
            $staffs = new \App\Models\Staff;
            $staffs = $staffs->sum("salary");
    
            $services = \App\Models\InvoiceService::with(['service', 'invoice']);
            if (request()->start_date && request()->end_date) {
                $services = $services->whereBetween("created_at", [request()->start_date, request()->end_date]);
            }
    
    
            $services = $services->get();
    
            $totalCommision = 0;
            foreach ($services as $s) {
                $totalCommision += $s["service"]["commercial"];
            }
    
            $expenses=new \App\Models\Expense;
            if (request()->start_date && request()->end_date) {
                $expenses = $expenses->whereBetween("created_at", [request()->start_date, request()->end_date]);
            }
    
            $expenses=$expenses->sum("amount");
    
            $profit=$totalInvoice[0]["item_total"]+$totalInvoice[0]["service_total"];
            $loss=$expenses+$totalCommision+$staffs+$totalInvoice[0]["item_buy_total"];
    
    
            return [
    
               
                "service_total" => $totalInvoice[0]["service_total"],
                "item_total" => $totalInvoice[0]["item_total"],
                "sale_total" => $totalInvoice[0]["final_total"],
               
                "item_buy_total" => $totalInvoice[0]["item_buy_total"],
                "staff_salary" => $staffs,
                "totalCommision" => $totalCommision,
                "expenseTotal"=>$expenses,
               
               
                "profit"=>$profit-$loss,
                
            ];
        }

        $totalInvoice = new \App\Models\Invoice;
        if (request()->start_date && request()->end_date) {
            $totalInvoice = $totalInvoice->whereBetween('created_at', [request()->start_date, request()->end_date]);
        }
        $totalInvoice = $totalInvoice->select(DB::raw('sum(final_total) as final_total'), DB::raw('sum(item_buy_total) as item_buy_total'), DB::raw('sum(item_total) as item_total'), DB::raw('sum(service_total) as service_total'));
        $totalInvoice = $totalInvoice->where("shop_id",request()->user()->shop_id)->get();

        $staffs = new \App\Models\Staff;
        $staffs = $staffs->where("shop_id",request()->user()->shop_id)->sum("salary");

        $services = \App\Models\InvoiceService::with(['service', 'invoice']);
        if (request()->start_date && request()->end_date) {
            $services = $services->whereBetween("created_at", [request()->start_date, request()->end_date]);
        }


        $services = $services->whereHas("invoice",function($query){
            $query->where("shop_id",request()->user()->shop_id);
        })->get();

        $totalCommision = 0;
        foreach ($services as $s) {
            $totalCommision += $s["service"]["commercial"];
        }

        $expenses=new \App\Models\Expense;
        if (request()->start_date && request()->end_date) {
            $expenses = $expenses->whereBetween("created_at", [request()->start_date, request()->end_date]);
        }

        $expenses=$expenses->where("shop_id",request()->user()->shop_id)->sum("amount");

        $profit=$totalInvoice[0]["item_total"]+$totalInvoice[0]["service_total"];
        $loss=$expenses+$totalCommision+$staffs+$totalInvoice[0]["item_buy_total"];


        return [

           
            "service_total" => $totalInvoice[0]["service_total"],
            "item_total" => $totalInvoice[0]["item_total"],
            "sale_total" => $totalInvoice[0]["final_total"],
           
            "item_buy_total" => $totalInvoice[0]["item_buy_total"],
            "staff_salary" => $staffs,
            "totalCommision" => $totalCommision,
            "expenseTotal"=>$expenses,
           
           
            "profit"=>$profit-$loss,
            
        ];
    }
}
