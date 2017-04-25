<?php
class Common {
	public static $l2k = array(
		// Equity Income Statement
		'Total Sales' => 'total_sales',
		'Cost of Good Sold' => 'cost_of_good_sold',
		'Gross Profit' => 'gross_profit',
		'Operation Expenses' => 'operation_expenses',
		'EBIT' => 'ebit',
		'Other Income/Expenses' => 'other_income_expenses',
		'Earning Before Tax' => 'earning_before_tax',
		'Net Income After Tax' => 'net_income_after_tax',
		'Minority Interest' => 'minority_interest',
		'Net Income(NI)' => 'net_income',
		'Earning Per Share(EPS)' => 'eps',
		'Book Value Per Share(BV)' => 'bv',
		'Close Price' => 'close_price',
		'PER(Colse Price/EPS*)' => 'per',
		'PBV(Close Price/BV)' => 'pbv',
		// Equity Balance Sheet
		'Cash & Equivalent' => 'cash_eq',
		'Account Receivable' => 'acc_rec',
		'Inventories' => 'inventories',
		'Other Current Assets' => 'other_curr_asset',
		'Total Current Assets' => 'total_curr_asset',
		'Fixed Assets' => 'fixed_asset',
		'Other Non-Curr.Assets' => 'other_noncurr_asset',
		'Tot.Non-Cuttent Assets' => 'total_noncurr_asset',
		'Total Assets' => 'total_asset',
		'Current Liabilities' => 'curr_liab',
		'Long Term Liabilties' => 'long_liab',
		'Total Libilitie' => 'total_liab',
		'Total Equity' => 'total_equity',
		'Minority Interest' => 'minor_interest',
		'Tot. Liabilities & Equity' => 'total_liab_equity',
		// Equity Cash Flow
		'Operating Activities' => 'operating_act',
		'Investing Activities' => 'investing_act',
		'Financing Activities' => 'financing_act',
		'Net Cash Flow Activities' => 'net_cash_flow_act',
		'Cash & equiv.Ending' => 'cash_eq_end',
		'PER (X) (ClostPrice/EPS*)' => 'per2',
		'PBV (X) (ClosePrice/BV)' => 'pbv2',
		'DER (X) (T.Liab/T.Eq)' => 'der',
		'ROA (X) (NI*/T.Assrts)' => 'roa',
		'ROE (X) (NI*/T.Equity)' => 'roe',
		'Op.Margin (%) (EBIT/Sales)' => 'op_margin',
    );
    public static $k2l;

	public static function has_label($label) {
		return isset(static::$l2k[$label]);
	}

	public static function label2key($label) {
		return static::$l2k[$label];
	}

	public static function has_key($key) {
		return isset(static::$k2l[$key]);
	}

	public static function key2label($key) {
		return static::$k2l[$key];
	}
}

Common::$k2l = array_flip(Common::$l2k);