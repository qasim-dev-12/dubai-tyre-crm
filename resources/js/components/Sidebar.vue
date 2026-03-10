<template>
  <!-- Main Sidebar Container -->
  <aside class="main-sidebar">
    <!-- Brand Logo -->
    <router-link :to="{ name: 'home' }" class="brand-link">

    <!-- HERE I cHANGED THE CODE -->

      <!-- <img v-if="appInfo" :src="appInfo.blackLogo" :alt="appInfo.companyName" class="lg-logo light-logo" />
      <img v-if="appInfo" :src="appInfo.logo" :alt="appInfo.companyName" class="lg-logo dark-logo" />
      <img v-if="appInfo" :src="appInfo.smallLogo" alt="appInfo.companyName" class="sm-logo" /> -->
      <p>800speedy</p>
    </router-link>

    <!-- Sidebar -->
    <div class="sidebar custom-sidebar">
      <!-- menu search -->

      <div class="search-area sidebar-search-wrapper mt-4">
        <div class="menu-search-btn" :class="[this.menuSearchQuery !== '' ? 'd-none' : 'd-inline-block']">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
            stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>
        <input ref="autoFocusInput" type="text" v-model="menuSearchQuery" @input="searchMenu"
          class="search-input form-control" :placeholder="$t('Search')">
        <label class="menu-search-btn" :class="[this.menuSearchQuery !== '' ? 'd-inline-block' : 'd-none']"
          @click="clearMenuSearch">
          <i class="fas fa-times" />
        </label>
      </div>
      <div v-if="menuSearchQuery" id="searchMenuResult">
        <nav class="py-3 pb-5">
          <ul v-if="menuItems.length" class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
            <li v-for="(menuItem, index) in menuItems" :key="index" class="nav-item">
              <router-link :to="{ name: menuItem.route }" class="nav-link">
                <i v-if="menuItem.icon" :class="menuItem.icon" />
                <p>{{ $t(menuItem.text) }}</p>
              </router-link>
            </li>
          </ul>
          <p v-else>{{ $t('No matching items found') }}</p>
        </nav>
      </div>
      <!-- Sidebar Menu -->
      <nav class="py-3 pb-5" :class="{ 'd-none': menuSearchQuery }">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
          <li class="nav-header text-uppercase text-bold">
            {{ $t("Dashboard") }}
          </li>
          <li class="nav-item">
            <router-link :to="{ name: 'home' }" class="nav-link">
              <i class="nav-icon fas fa-home" />
              <p>{{ $t("Dashboard") }}</p>
            </router-link>
          </li>
          <li class="nav-header text-bold">{{ $t("ACTIVITIES") }}</li>
          <li v-if="$can('expense-category-list') ||
            $can('expense-category-create') ||
            $can('expense-category-edit') ||
            $can('expense-category-delete') ||
            $can('expense-sub-category-list') ||
            $can('expense-sub-category-create') ||
            $can('expense-sub-category-edit') ||
            $can('expense-sub-category-delete') ||
            $can('expense-list') ||
            $can('expense-create') ||
            $can('expense-edit') ||
            $can('expense-view') ||
            $can('expense-delete')
          " class="nav-item has-treeview" :class="menuOpen('expenseCats') ||
            menuOpen('expenseSubCats') ||
            menuOpen('expenses')
            ? 'menu-is-opening menu-open'
            : ''
            ">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-calculator" />
              <p>
                {{ $t("Expenses") }}
                <i class="fas fa-angle-left right" />
              </p>
            </a>
            <ul class="nav nav-treeview" :class="menuOpen('expenseCats') ||
              menuOpen('expenseSubCats') ||
              menuOpen('expenses')
              ? 'display: block'
              : 'display: none'
              ">
              <li v-if="$can('expense-category-list') ||
                $can('expense-category-create') ||
                $can('expense-category-edit') ||
                $can('expense-category-delete')
              " class="nav-item">
                <router-link :to="{ name: 'expenseCats.index' }" class="nav-link">
                  <i class="fas fa-tags nav-icon" />
                  <p>{{ $t("Categories") }}</p>
                </router-link>
              </li>
              <li v-if="$can('expense-sub-category-list') ||
                $can('expense-sub-category-create') ||
                $can('expense-sub-category-edit') ||
                $can('expense-sub-category-delete')
              " class="nav-item">
                <router-link :to="{ name: 'expenseSubCats.index' }" class="nav-link">
                  <i class="fas fa-code-branch nav-icon" />
                  <p>{{ $t("Sub Categories") }}</p>
                </router-link>
              </li>
              <li v-if="$can('expense-list') ||
                $can('expense-create') ||
                $can('expense-edit') ||
                $can('expense-view') ||
                $can('expense-delete')
              " class="nav-item">
                <router-link :to="{ name: 'expenses.index' }" class="nav-link">
                  <i class="fas fa-list-ul nav-icon" />
                  <p>{{ $t("Expenses List") }}</p>
                </router-link>
              </li>

            </ul>
          </li>
          <li v-if="$can('purchase-list') ||
            $can('purchase-create') ||
            $can('purchase-edit') ||
            $can('purchase-view') ||
            $can('purchase-delete') ||
            $can('purchase-return-list') ||
            $can('purchase-return-create') ||
            $can('purchase-return-edit') ||
            $can('purchase-return-view') ||
            $can('purchase-return-delete')
          " class="nav-item has-treeview" :class="menuOpen('purchases') || menuOpen('purchaseReturns')
            ? 'menu-is-opening menu-open'
            : ''
            ">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-shopping-basket" />
              <p>
                {{ $t("Purchases") }}
                <i class="right fas fa-angle-left" />
              </p>
            </a>
            <ul class="nav nav-treeview" :style="menuOpen('purchases') || menuOpen('purchaseReturns')
              ? 'display: block'
              : 'display: none'
              ">
              <li v-if="$can('purchase-list') ||
                $can('purchase-create') ||
                $can('purchase-edit') ||
                $can('purchase-view') ||
                $can('purchase-delete')
              " class="nav-item">
                <router-link :to="{ name: 'purchases.index' }" class="nav-link">
                  <i class="fas fa-truck-loading nav-icon" />
                  <p>{{ $t("Purchases List") }}</p>
                </router-link>
              </li>
              <li v-if="$can('purchase-return-list') ||
                $can('purchase-return-create') ||
                $can('purchase-return-edit') ||
                $can('purchase-return-view') ||
                $can('purchase-return-delete')
              " class="nav-item">
                <router-link :to="{ name: 'purchaseReturns.index' }" class="nav-link">
                  <i class="fas fa-undo-alt nav-icon" />
                  <p>{{ $t("Returns List") }}</p>
                </router-link>
              </li>
            </ul>
          </li>
          <li v-if="$can('quotation-list') ||
            $can('quotation-create') ||
            $can('quotation-view') ||
            $can('quotation-edit') ||
            $can('quotation-delete') ||
            $can('quotation-to-invoice') ||
            $can('invoice-list') ||
            $can('invoice-create') ||
            $can('invoice-view') ||
            $can('invoice-edit') ||
            $can('invoice-delete') ||
            $can('invoice-return-list') ||
            $can('invoice-return-create') ||
            $can('invoice-return-view') ||
            $can('invoice-return-edit') ||
            $can('invoice-return-delete') ||
            $can('job-list')
          " class="nav-item has-treeview" :class="menuOpen('quotations') ||
            menuOpen('invoices') ||
            menuOpen('invoiceReturns')
            ? 'menu-is-opening menu-open'
            : ''
            ">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-shopping-bag" />
              <p>
                {{ $t("Sales") }}
                <i class="right fas fa-angle-left" />
              </p>
            </a>
            <ul class="nav nav-treeview" :style="menuOpen('quotations') ||
              menuOpen('invoices') ||
              menuOpen('invoiceReturns')
              ? 'display: block'
              : 'display: none'
              ">
              <li v-if="$can('quotation-list') ||
                $can('quotation-create') ||
                $can('quotation-view') ||
                $can('quotation-edit') ||
                $can('quotation-delete') ||
                $can('quotation-to-invoice')
              " class="nav-item">
                <router-link :to="{ name: 'quotations.index' }" class="nav-link">
                  <i class="fas fa-th-list nav-icon" />
                  <p>{{ $t("Quotations List") }}</p>
                </router-link>
              </li>
              <li v-if="$can('invoice-list') ||
                $can('invoice-create') ||
                $can('invoice-view') ||
                $can('invoice-edit') ||
                $can('invoice-delete')
              " class="nav-item">
                <router-link :to="{ name: 'invoices.index' }" class="nav-link">
                  <i class="fas fa-file-invoice nav-icon" />
                  <p>{{ $t("Invoices List") }}</p>
                </router-link>
              </li>
              <li v-if="$can('job-list')" class="nav-item">
                  <router-link :to="{ name: 'jobs.index' }" class="nav-link">
    <i class="fas fa-briefcase nav-icon" />
    <p>Jobs</p>
  </router-link>
              </li>



              <li v-if="$can('invoice-list') ||
                $can('invoice-create') ||
                $can('invoice-view') ||
                $can('invoice-edit') ||
                $can('invoice-delete')
              " class="nav-item">
                <router-link :to="{ name: 'pos.create' }" class="nav-link">
                  <i class="fas fa-cash-register nav-icon"></i>
                  <p>{{ $t("POS") }}</p>
                </router-link>
              </li>
              <li v-if="$can('invoice-return-list') ||
                $can('invoice-return-create') ||
                $can('invoice-return-view') ||
                $can('invoice-return-edit') ||
                $can('invoice-return-delete')
              " class="nav-item">
                <router-link :to="{ name: 'invoiceReturns.index' }" class="nav-link">
                  <i class="fas fa-undo-alt nav-icon" />
                  <p>{{ $t("Returns List") }}</p>
                </router-link>
              </li>

            </ul>


          </li>
          <li class="nav-item">
          <a class="nav-link" >
           <router-link  :to="{ name: 'leads.index' }" class="nav-link"  style="padding-left: 0; padding-right: 0;">
  <i class=" nav-icon fas fa-user-plus"></i>
  <span>Leads</span>
</router-link>

</a>

</li>





          <li class="nav-header text-bold">{{ $t("ACCOUNTING") }}</li>
          <li v-if="$can('account-list') ||
            $can('account-create') ||
            $can('account-view') ||
            $can('account-edit') ||
            $can('account-delete') ||
            $can('account-balance-list') ||
            $can('account-balance-create') ||
            $can('account-balance-edit') ||
            $can('account-balance-delete') ||
            $can('account-transfer-balance-list') ||
            $can('account-transfer-balance-create') ||
            $can('account-transfer-balance-edit') ||
            $can('account-transfer-balance-view') ||
            $can('account-transfer-balance-delete') ||
            $can('transaction-history')
          " class="nav-item has-treeview" :class="menuOpen('accounts') ||
            menuOpen('balances') ||
            menuOpen('transferBalances') ||
            menuOpen('transactions')
            ? 'menu-is-opening menu-open'
            : ''
            ">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-book" />
              <p>
                {{ $t("Cash Book") }}
                <i class="right fas fa-angle-left" />
              </p>
            </a>
            <ul class="nav nav-treeview" :style="menuOpen('accounts') ||
              menuOpen('balances') ||
              menuOpen('transferBalances') ||
              menuOpen('transactions')
              ? 'display: block'
              : 'display: none'
              ">
              <li v-if="$can('account-list') ||
                $can('account-create') ||
                $can('account-view') ||
                $can('account-edit') ||
                $can('account-delete')
              " class="nav-item">
                <router-link :to="{ name: 'accounts.index' }" class="nav-link">
                  <i class="fas fa-grip-horizontal nav-icon" />
                  <p>{{ $t("Accounts") }}</p>
                </router-link>
              </li>
              <li v-if="$can('account-balance-list') ||
                $can('account-balance-create') ||
                $can('account-balance-edit') ||
                $can('account-balance-delete')
              " class="nav-item">
                <router-link :to="{ name: 'balances.index' }" class="nav-link">
                  <i class="fas fa-sliders-h nav-icon" />
                  <p>{{ $t("Balance Adjustments") }}</p>
                </router-link>
              </li>
              <li v-if="$can('account-transfer-balance-list') ||
                $can('account-transfer-balance-create') ||
                $can('account-transfer-balance-edit') ||
                $can('account-transfer-balance-view') ||
                $can('account-transfer-balance-delete')
              " class="nav-item">
                <router-link :to="{ name: 'transferBalances.index' }" class="nav-link">
                  <i class="fas fa-exchange-alt nav-icon" />
                  <p>{{ $t("Balance Transfers") }}</p>
                </router-link>
              </li>
              <li v-if="$can('transaction-history')" class="nav-item">
                <router-link :to="{ name: 'transactions.index' }" class="nav-link">
                  <i class="fas fa-history nav-icon" />
                  <p>{{ $t("Transaction History") }}</p>
                </router-link>
              </li>
            </ul>
          </li>
          <li v-if="$can('non-purchase-payment-list') ||
            $can('non-purchase-payment-create') ||
            $can('non-purchase-payment-edit') ||
            $can('non-purchase-payment-view') ||
            $can('non-purchase-payment-delete') ||
            $can('purchase-payment-list') ||
            $can('purchase-payment-create') ||
            $can('purchase-payment-edit') ||
            $can('purchase-payment-view') ||
            $can('purchase-payment-delete') ||
            $can('non-invoice-payment-list') ||
            $can('non-invoice-payment-create') ||
            $can('non-invoice-payment-edit') ||
            $can('non-invoice-payment-view') ||
            $can('non-invoice-payment-delete') ||
            $can('invoice-payment-list') ||
            $can('invoice-payment-create') ||
            $can('invoice-payment-view') ||
            $can('invoice-payment-edit') ||
            $can('invoice-payment-delete')
          " class="nav-item has-treeview" :class="menuOpen('nonInvoicePayments') ||
            menuOpen('invoicePayments') ||
            menuOpen('purchasePayments') ||
            menuOpen('nonPurchasePayments')
            ? 'menu-is-opening menu-open'
            : ''
            ">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-receipt" />
              <p>
                {{ $t("Payments") }}
                <i class="right fas fa-angle-left" />
              </p>
            </a>
            <ul class="nav nav-treeview" :style="menuOpen('nonInvoicePayments') ||
              menuOpen('invoicePayments') ||
              menuOpen('purchasePayments') ||
              menuOpen('nonPurchasePayments')
              ? 'display: block'
              : 'display: none'
              ">
              <li v-if="$can('non-invoice-payment-list') ||
                $can('non-invoice-payment-create') ||
                $can('non-invoice-payment-edit') ||
                $can('non-invoice-payment-view') ||
                $can('non-invoice-payment-delete') ||
                $can('invoice-payment-list') ||
                $can('invoice-payment-create') ||
                $can('invoice-payment-view') ||
                $can('invoice-payment-edit') ||
                $can('invoice-payment-delete')
              " class="nav-item has-treeview" :class="menuOpen('nonInvoicePayments') || menuOpen('invoicePayments')
                ? 'menu-is-opening menu-open'
                : ''
                ">
                <a href="#" class="nav-link">
                  <i class="nav-icon fas fa-users" />
                  <p>
                    {{ $t("Clients") }}
                    <i class="fas fa-angle-left right" />
                  </p>
                </a>
                <ul class="nav nav-treeview" :style="menuOpen('nonInvoicePayments') ||
                  menuOpen('invoicePayments')
                  ">
                  <li v-if="$can('invoice-payment-list') ||
                    $can('invoice-payment-create') ||
                    $can('invoice-payment-view') ||
                    $can('invoice-payment-edit') ||
                    $can('invoice-payment-delete')
                  " class="nav-item">
                    <router-link :to="{ name: 'invoicePayments.index' }" class="nav-link">
                      <i class="fas fa-file-invoice nav-icon" />
                      <p>{{ $t("Invoice") }}</p>
                    </router-link>
                  </li>
                  <li v-if="$can('non-invoice-payment-list') ||
                    $can('non-invoice-payment-create') ||
                    $can('non-invoice-payment-edit') ||
                    $can('non-invoice-payment-view') ||
                    $can('non-invoice-payment-delete')
                  " class="nav-item">
                    <router-link :to="{ name: 'nonInvoicePayments.index' }" class="nav-link">
                      <i class="fas fa-file-alt nav-icon" />
                      <p>{{ $t("Non Invoice") }}</p>
                    </router-link>
                  </li>
                </ul>
              </li>

              <li v-if="$can('purchase-payment-list') ||
                $can('purchase-payment-create') ||
                $can('purchase-payment-edit') ||
                $can('purchase-payment-view') ||
                $can('purchase-payment-delete') ||
                $can('non-purchase-payment-list') ||
                $can('non-purchase-payment-create') ||
                $can('non-purchase-payment-edit') ||
                $can('non-purchase-payment-view') ||
                $can('non-purchase-payment-delete')
              " class="nav-item has-treeview" :class="menuOpen('nonPurchasePayments') ||
                menuOpen('purchasePayments')
                ? 'menu-is-opening menu-open'
                : ''
                ">
                <a href="#" class="nav-link">
                  <i class="nav-icon fas fa-people-carry" />
                  <p>
                    {{ $t("Suppliers") }}
                    <i class="fas fa-angle-left right" />
                  </p>
                </a>
                <ul class="nav nav-treeview" :style="menuOpen('nonPurchasePayments') ||
                  menuOpen('purchasePayments')
                  ? 'display: block'
                  : 'display: none'
                  ">
                  <li v-if="$can('purchase-payment-list') ||
                    $can('purchase-payment-create') ||
                    $can('purchase-payment-edit') ||
                    $can('purchase-payment-view') ||
                    $can('purchase-payment-delete')
                  " class="nav-item">
                    <router-link :to="{ name: 'purchasePayments.index' }" class="nav-link">
                      <i class="fas fa-plane-departure nav-icon" />
                      <p>{{ $t("Purchase") }}</p>
                    </router-link>
                  </li>
                  <li v-if="$can('non-purchase-payment-list') ||
                    $can('non-purchase-payment-create') ||
                    $can('non-purchase-payment-edit') ||
                    $can('non-purchase-payment-view') ||
                    $can('non-purchase-payment-delete')
                  " class="nav-item">
                    <router-link :to="{ name: 'nonPurchasePayments.index' }" class="nav-link">
                      <i class="fas fa-truck-pickup nav-icon" />
                      <p>{{ $t("Non Purchase") }}</p>
                    </router-link>
                  </li>
                </ul>
              </li>
            </ul>
          </li>
          <li v-if="$can('loan-authority-list') ||
            $can('loan-authority-create') ||
            $can('loan-authority-view') ||
            $can('loan-authority-edit') ||
            $can('loan-authority-delete') ||
            $can('loan-list') ||
            $can('loan-create') ||
            $can('loan-view') ||
            $can('loan-edit') ||
            $can('loan-delete') ||
            $can('loan-payment-list') ||
            $can('loan-payment-create') ||
            $can('loan-payment-view') ||
            $can('loan-payment-edit') ||
            $can('loan-payment-delete')
          " class="nav-item has-treeview" :class="menuOpen('authorities') ||
            menuOpen('loans') ||
            menuOpen('loanPayments')
            ? 'menu-is-opening menu-open'
            : ''
            ">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-piggy-bank" />
              <p>
                {{ $t("Loan Management") }}
                <i class="right fas fa-angle-left" />
              </p>
            </a>
            <ul class="nav nav-treeview" :style="menuOpen('authorities') ||
              menuOpen('loans') ||
              menuOpen('loanPayments')
              ? 'display: block'
              : 'display: none'
              ">
              <li v-if="$can('loan-authority-list') ||
                $can('loan-authority-create') ||
                $can('loan-authority-view') ||
                $can('loan-authority-edit') ||
                $can('loan-authority-delete')
              " class="nav-item">
                <router-link :to="{ name: 'authorities.index' }" class="nav-link">
                  <i class="fas fa-building nav-icon" />
                  <p>{{ $t("Authorities") }}</p>
                </router-link>
              </li>
              <li v-if="$can('loan-list') ||
                $can('loan-create') ||
                $can('loan-view') ||
                $can('loan-edit') ||
                $can('loan-delete')
              " class="nav-item">
                <router-link :to="{ name: 'loans.index' }" class="nav-link">
                  <i class="fas fa-list-ul nav-icon" />
                  <p>{{ $t("Loans") }}</p>
                </router-link>
              </li>
              <li v-if="$can('loan-payment-list') ||
                $can('loan-payment-create') ||
                $can('loan-payment-view') ||
                $can('loan-payment-edit') ||
                $can('loan-payment-delete')
              " class="nav-item">
                <router-link :to="{ name: 'loanPayments.index' }" class="nav-link">
                  <i class="fas fa-receipt nav-icon" />
                  <p>{{ $t("Payments") }}</p>
                </router-link>
              </li>
            </ul>
          </li>
          <li v-if="$can('asset-type-list') ||
            $can('asset-type-create') ||
            $can('asset-type-edit') ||
            $can('asset-type-delete') ||
            $can('asset-list') ||
            $can('asset-create') ||
            $can('asset-view') ||
            $can('asset-edit') ||
            $can('asset-delete')
          " class="nav-item has-treeview" :class="menuOpen('assetTypes') || menuOpen('assets')
            ? 'menu-is-opening menu-open'
            : ''
            ">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-couch" />
              <p>
                {{ $t("Asset Management") }}
                <i class="right fas fa-angle-left" />
              </p>
            </a>
            <ul class="nav nav-treeview" :style="menuOpen('assetTypes') || menuOpen('assets')
              ? 'display: block'
              : 'display: none'
              ">
              <li v-if="$can('asset-type-list') ||
                $can('asset-type-create') ||
                $can('asset-type-edit') ||
                $can('asset-type-delete')
              " class="nav-item">
                <router-link :to="{ name: 'assetTypes.index' }" class="nav-link">
                  <i class="fas fa-tags nav-icon" />
                  <p>{{ $t("Types") }}</p>
                </router-link>
              </li>
              <li v-if="$can('asset-list') ||
                $can('asset-create') ||
                $can('asset-view') ||
                $can('asset-edit') ||
                $can('asset-delete')
              " class="nav-item">
                <router-link :to="{ name: 'assets.index' }" class="nav-link">
                  <i class="fas fa-list-ul nav-icon" />
                  <p>{{ $t("Assets") }}</p>
                </router-link>
              </li>
            </ul>
          </li>
          <li v-if="$can('payroll-list') ||
            $can('payroll-create') ||
            $can('payroll-view') ||
            $can('payroll-edit') ||
            $can('payroll-delete')
          " class="nav-item">
            <router-link :to="{ name: 'payroll.index' }" class="nav-link">
              <i class="nav-icon fas fa-clipboard-list" />
              <p>{{ $t("Payroll") }}</p>
            </router-link>
          </li>
          <li class="nav-header text-bold">{{ $t("PEOPLE") }}</li>
          <li v-if="$can('client-list') ||
            $can('client-create') ||
            $can('client-view') ||
            $can('client-edit') ||
            $can('client-delete')
          " class="nav-item">
            <router-link :to="{ name: 'clients.index' }" class="nav-link">
              <i class="nav-icon fas fa-users" />
              <p>{{ $t("Clients") }}</p>
            </router-link>
          </li>
          <li v-if="$can('supplier-list') ||
            $can('supplier-create') ||
            $can('supplier-view') ||
            $can('supplier-edit') ||
            $can('supplier-delete')
          " class="nav-item">
            <router-link :to="{ name: 'suppliers.index' }" href="#" class="nav-link">
              <i class="nav-icon fas fa-people-carry" />
              <p>{{ $t("Suppliers") }}</p>
            </router-link>
          </li>
          <li v-if="$can('department-list') ||
            $can('department-create') ||
            $can('department-edit') ||
            $can('department-delete') ||
            $can('employee-list') ||
            $can('employee-create') ||
            $can('employee-edit') ||
            $can('employee-delete') ||
            $can('employee-view') ||
            $can('increment-list') ||
            $can('increment-create') ||
            $can('increment-edit') ||
            $can('increment-view') ||
            $can('increment-delete')
          " class="nav-item has-treeview" :class="menuOpen('departments') ||
            menuOpen('employees') ||
            menuOpen('increments')
            ? 'menu-is-opening menu-open'
            : ''
            ">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-users-cog" />
              <p>
                {{ $t("Employees") }}
                <i class="fas fa-angle-left right" />
              </p>
            </a>
            <ul class="nav nav-treeview" :style="menuOpen('departments') ||
              menuOpen('employees') ||
              menuOpen('increments')
              ? 'display: block'
              : 'display: none'
              ">
              <li v-if="$can('department-list') ||
                $can('department-create') ||
                $can('department-edit') ||
                $can('department-delete')
              " class="nav-item">
                <router-link :to="{ name: 'departments.index' }" class="nav-link">
                  <i class="fas fa-server nav-icon" />
                  <p>{{ $t("Departments") }}</p>
                </router-link>
              </li>
              <li v-if="$can('employee-list') ||
                $can('employee-create') ||
                $can('employee-edit') ||
                $can('employee-delete') ||
                $can('employee-view')
              " class="nav-item">
                <router-link :to="{ name: 'employees.index' }" class="nav-link">
                  <i class="fas fa-list-ul nav-icon" />
                  <p>{{ $t("Employees List") }}</p>
                </router-link>
              </li>
              <li v-if="$can('increment-list') ||
                $can('increment-create') ||
                $can('increment-edit') ||
                $can('increment-view') ||
                $can('increment-delete')
              " class="nav-item">
                <router-link :to="{ name: 'increments.index' }" class="nav-link">
                  <i class="fas fa-list-ul nav-icon" />
                  <p>{{ $t("Increments") }}</p>
                </router-link>
              </li>
            </ul>
          </li>

          <li class="nav-header text-bold">
            {{ $t("INVENTORY") }}
          </li>
          <li v-if="$can('product-category-create') ||
            $can('product-category-edit') ||
            $can('product-category-delete') ||
            $can('product-sub-category-create') ||
            $can('product-sub-category-edit') ||
            $can('product-sub-category-delete') ||
            $can('product-create') ||
            $can('product-view') ||
            $can('product-edit') ||
            $can('product-delete') ||
            $can('print-barcode')
          " class="nav-item has-treeview" :class="menuOpen('productCats') ||
            menuOpen('productSubCats') ||
            menuOpen('products') ||
            menuOpen('barcode')
            ? 'menu-is-opening menu-open'
            : ''
            ">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-boxes" />
              <p>
                {{ $t('Items') }}
                <i class="fas fa-angle-left right" />
              </p>
            </a>
            <ul class="nav nav-treeview" :style="menuOpen('productCats') ||
              menuOpen('productSubCats') ||
              menuOpen('products') ||
              menuOpen('barcode')
              ? 'display: block'
              : 'display: none'
              ">
              <li v-if="$can('product-category-create') ||
                $can('product-category-edit') ||
                $can('product-category-delete')
              " class="nav-item">
                <router-link :to="{ name: 'productCats.index' }" class="nav-link">
                  <i class="fas fa-tags nav-icon" />
                  <p>{{ $t("Categories") }}</p>
                </router-link>
              </li>
              <li v-if="$can('product-sub-category-create') ||
                $can('product-sub-category-edit') ||
                $can('product-sub-category-delete')
              " class="nav-item">
                <router-link :to="{ name: 'productSubCats.index' }" class="nav-link">
                  <i class="fas fa-code-branch nav-icon" />
                  <p>{{ $t("Sub Categories") }}</p>
                </router-link>
              </li>
              <li v-if="$can('product-create') ||
                $can('product-view') ||
                $can('product-edit') ||
                $can('product-delete')
              " class="nav-item">
                <router-link :to="{ name: 'products.index' }" class="nav-link">
                  <i class="fas fa-list-ul nav-icon" />
                  <p>{{ $t('Item List') }}</p>
                </router-link>
              </li>

              <li v-if="$can('print-barcode')" class="nav-item">
                <router-link :to="{ name: 'barcode.print' }" class="nav-link">
                  <i class="fas fa-barcode nav-icon" />
                  <p>{{ $t("Barcode") }}</p>
                </router-link>
              </li>
            </ul>
          </li>

          <li v-if="$can('inventory') ||
            $can('adjustment-create') ||
            $can('adjustment-view') ||
            $can('adjustment-edit') ||
            $can('adjustment-delete')
          " class="nav-item has-treeview" :class="menuOpen('inventory') ||
            menuOpen('adjustments') ||
            menuOpen('nonzeroInventory')
            ? 'menu-is-opening menu-open'
            : ''
            ">
            <a href="" class="nav-link">
              <i class="nav-icon fas fa-warehouse" />
              <p>
                {{ $t("Inventory") }}
                <i class="right fas fa-angle-left" />
              </p>
            </a>
            <ul class="nav nav-treeview" :style="menuOpen('inventory') ||
              menuOpen('adjustments') ||
              menuOpen('nonzeroInventory')
              ? 'display: block'
              : 'display: none'
              ">
              <li v-if="$can('inventory-history')" class="nav-item">
                <router-link :to="{ name: 'inventory.index' }" class="nav-link">
                  <i class="fas fa-pallet nav-icon" />
                  <p>{{ $t("View Inventory") }}</p>
                </router-link>
              </li>
              <li v-if="$can('inventory-history')" class="nav-item">
                <router-link :to="{ name: 'nonzeroInventory.index' }" class="nav-link">
                  <i class="fas fa-boxes nav-icon" />
                  <p>{{ $t("Non Zero Inventory") }}</p>
                </router-link>
              </li>
              <li v-if="$can('adjustment-create') ||
                $can('adjustment-view') ||
                $can('adjustment-edit') ||
                $can('adjustment-delete')
              " class="nav-item">
                <router-link :to="{ name: 'adjustments.index' }" class="nav-link">
                  <i class="fas fa-sliders-h nav-icon" />
                  <p>{{ $t("Inventory Adjustment") }}</p>
                </router-link>
              </li>
            </ul>
          </li>

          <li class="nav-header text-bold">{{ $t("REPORTS") }}</li>
          <li v-if="$can('balance-sheet')" class="nav-item">
            <router-link :to="{ name: 'reports.balanceSheet' }" class="nav-link">
              <i class="fas fa-file-invoice-dollar nav-icon" />
              <p>{{ $t("Balance Sheet") }}</p>
            </router-link>
          </li>
          <!-- today profit -->
          <li v-if="$can('today-profit')" class="nav-item">
            <router-link :to="{ name: 'reports.todayReport' }" class="nav-link">
              <i class="fas fa-file-invoice-dollar nav-icon" />
              <p>{{ $t("Today Report") }}</p>
            </router-link>
          </li>
          <li v-if="$can('summary-report')" class="nav-item">
            <router-link :to="{ name: 'reports.summary' }" class="nav-link">
              <i class="fas fa-file-contract nav-icon" />
              <p>{{ $t("Summary Report") }}</p>
            </router-link>
          </li>
          <li v-if="$can('profit-loss')" class="nav-item">
            <router-link :to="{ name: 'reports.profitLoss' }" class="nav-link">
              <i class="fas fa-chart-line nav-icon" />
              <p>{{ $t("Profit/Loss Report") }}</p>
            </router-link>
          </li>
          <li v-if="$can('expense-report')" class="nav-item">
            <router-link :to="{ name: 'reports.expenses' }" class="nav-link">
              <i class="fas fa-chart-pie nav-icon" />
              <p>{{ $t("Expense Report") }}</p>
            </router-link>
          </li>
          <li v-if="$can('item-report')" class="nav-item">
            <router-link :to="{ name: 'reports.items' }" class="nav-link">
              <i class="fas fa-chart-bar nav-icon" />
              <p>{{ $t("Item Report") }}</p>
            </router-link>
          </li>
          <li v-if="$can('inventory-report')" class="nav-item">
            <router-link :to="{ name: 'reports.inventory' }" class="nav-link">
              <i class="fas fa-chart-pie nav-icon" />
              <p>{{ $t("Inventory Report") }}</p>
            </router-link>
          </li>
          <li v-if="$can('balance-sheet')" class="nav-item">
            <router-link :to="{ name: 'reports.supplierPayableReport' }" class="nav-link">
              <i class="fas fa-file-invoice-dollar nav-icon" />
              <p>{{ $t("Supplier Payable Report") }}</p>
            </router-link>
          </li>
          <li v-if="$can('balance-sheet')" class="nav-item">
            <router-link :to="{ name: 'reports.clientReceivableReport' }" class="nav-link">
              <i class="fas fa-file-invoice-dollar nav-icon" />
              <p>{{ $t("Client Receivable Report") }}</p>
            </router-link>
          </li>
          <li v-if="$can('sales-by-user-report')" class="nav-item">
            <router-link :to="{ name: 'reports.salesByUserReport' }" class="nav-link">
              <i class="fas fa-file-invoice-dollar nav-icon" />
              <p>{{ $t("Sales By User Report") }}</p>
            </router-link>
          </li>
          <li v-if="$can('collection-by-user-report')" class="nav-item">
            <router-link :to="{ name: 'reports.collectionByUserReport' }" class="nav-link">
              <i class="fas fa-file-invoice-dollar nav-icon" />
              <p>{{ $t("Collection By User Report") }}</p>
            </router-link>
          </li>
          <li class="nav-header text-bold">{{ $t("ACCOUNT") }}</li>
          <li v-if="$can('role-permissions') ||
            $can('units') ||
            $can('currencies') ||
            $can('general-settings')
          " class="nav-item">
            <router-link :to="{ name: 'setup.general' }" class="nav-link">
              <i class="nav-icon fas fa-cogs" />
              <p>{{ $t("Setup") }}</p>
            </router-link>
          </li>

          <li class="nav-item">
            <router-link :to="{ name: 'activity.log' }" class="nav-link">
              <i class="nav-icon fa fa-bell" aria-hidden="true"></i>
              <p>{{ $t("Activity Log") }}</p>
            </router-link>
          </li>

          <li v-if="$can('database-backup')" class="nav-item">
            <router-link :to="{ name: 'applicationUpdate.index' }" class="nav-link">
              <i class="nav-icon fas fa-upload" />
              <p>{{ $t("Update Application") }}</p>
            </router-link>
          </li>

          <!-- Reveal when woocommerce addon is ready -->
          <!-- <li class="nav-item">
            <router-link :to="{ name: 'addons' }" class="nav-link">
              <i class="fa fa-plug nav-icon" />
              <p>{{ $t("Addons") }}</p>
            </router-link>
          </li> -->


          <li v-if="appInfo.isWoocommerceInstall == 'true' && appInfo.isWoocommerceActive == 'true'"
            class="nav-item has-treeview">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-download" />
              <p>
                {{ $t("Woocommerce") }}
                <i class="right fas fa-angle-left" />
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <router-link :to="{ name: 'woocommerce' }" class="nav-link">
                  <i class="nav-icon fas fa-download" />
                  <p>{{ $t("Woocommerce") }}</p>
                </router-link>
              </li>
              <li class="nav-item">
                <router-link :to="{ name: 'woocommerce.syncLog' }" class="nav-link">
                  <i class="nav-icon fas fa-download" />
                  <p>{{ $t("Sync Log") }}</p>
                </router-link>
              </li>
              <li class="nav-item">
                <router-link :to="{ name: 'Settings' }" class="nav-link">
                  <i class="nav-icon fas fas fa-cogs" />
                  <p>{{ $t("Settings") }}</p>
                </router-link>
              </li>
            </ul>
          </li>


          <li class="nav-item has-treeview">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-user" />
              <p>
                {{ $t("Account") }}
                <i class="right fas fa-angle-left" />
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li v-if="$can('update-profile')" class="nav-item">
                <router-link :to="{ name: 'profile' }" class="nav-link">
                  <i class="nav-icon fas fa-user-circle" />
                  <p>{{ $t("Profile") }}</p>
                </router-link>
              </li>
              <li class="nav-item">
                <a class="nav-link admin-logout" href="#" @click.prevent="logout">
                  <i class="nav-icon fas fa-power-off" />
                  {{ $t("Logout") }}
                </a>
              </li>
            </ul>
          </li>
          <li class="nav-item has-treeview">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-user" />
              <p>
                {{ $t("Resources") }}
                <i class="right fas fa-angle-left" />
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a class="nav-link" href="/system-info" target="__blank">
                  <i class="nav-icon fas fa-info" />
                  <p>{{ $t("System Info") }}</p>
                </a>
              </li>
              <li class="nav-item">
                <a @click="executeAction('optimize:clear')" class="nav-link cursor-pointer" href="#!">
                  <i class="nav-icon fas fa-trash" />
                  <span>{{ $t("Clear Cache") }}</span>
                </a>
              </li>
              <li class="nav-item">
                <router-link :to="{ name: 'backup' }" class="nav-link">
                  <i class="nav-icon fas fa-download" />
                  <p>{{ $t("Database Backup") }}</p>
                </router-link>
              </li>
              <li class="nav-item">
                <a href="https://docs.codeshaper.tech/acculance/" class="nav-link" target="__blank">
                  <i class="nav-icon fas fa-book" />
                  <p>{{ $t("Documentation") }}</p>
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="https://codeshaperbd.freshdesk.com/support/login" target="__blank">
                  <i class="nav-icon fas fa-ticket-alt" />
                  <p>{{ $t("Support") }}</p>
                </a>
              </li>
            </ul>
          </li>
        </ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>
</template>

<script>
import axios from "axios";
import { mapGetters } from "vuex";
export default {
  data: () => ({
    appName: window.config.appName,
    menuSearchQuery: "",
    menuItems: [],
  }),
  // Map Getters
  computed: {
    ...mapGetters("operations", ["appInfo"]),
  },
  mounted() {
    $('[data-widget="treeview"]').Treeview("init");
  },
  methods: {
    menuOpen(routeName) {
      if (this.$route.name) {
        return this.$route.name.indexOf(routeName) > -1 ? true : false;
      }
      return false;
    },

    clearMenuSearch() {
      this.menuSearchQuery = "";
    },

    searchMenu() {
      const menuSearchQuery = this.menuSearchQuery;
      axios.get('/api/menu-search', {
        params: {
          menuSearchQuery: menuSearchQuery
        }
      })
        .then((response) => {
          const fetchedMenuItems = response.data.result.map(item => ({
            route: item.route,
            text: item.text,
            icon: item.icon
          }));
          this.menuItems = fetchedMenuItems;
        }).catch((error) => {
          toast.fire({ type: "error", title: this.$t("Opps...something went wrong") });
        })
    },

    async executeAction(command) {
      await axios
        .get("/api/server?command=" + command)
        .then(({ data }) => {
          toast.fire({
            type: "success",
            title: "Cache cleared successfully!",
          });
        })
        .catch(() => {
          toast.fire({ type: "error", title: this.$t("Opps...something went wrong") });
        });
    },

    async logout() {
      // Log out the user.
      await this.$store.dispatch("auth/logout");
      // Redirect to login.
      this.$router.push({ name: "login" });
    },
  },
};
</script>

<style>
.search-area input {
  padding: 5px 11px 5px 35px;
  border-radius: 5px;
}

.sidebar-search-wrapper {
  position: relative;
}

.menu-search-btn {
  position: absolute;
  left: 10px;
  top: 50%;
  transform: translateY(-50%);
}
</style>
