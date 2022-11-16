<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://brandsgateway.com
 * @since      1.0.0
 *
 * @package    Wordpress_Meilisearch
 * @subpackage Wordpress_Meilisearch/admin/partials
 */
?>

<div class="plugin-dashboard-wrapper max-w-7xl px-4 sm:px-6 lg:px-8">
    <!-- This file should primarily consist of HTML with a little bit of PHP. -->
    <h2 class="mt-8 max-w-6xl text-lg font-medium leading-6 text-gray-900">All Indexes</h2>

    <!-- Activity table (small breakpoint and up) -->
    <div class="mt-2 flex flex-col">
            <div class="min-w-full overflow-hidden overflow-x-auto align-middle shadow sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                    <tr>
                        <th class="bg-gray-50 px-6 py-3 text-left text-sm font-semibold text-gray-900" scope="col">Index Name</th>
                        <th class="bg-gray-50 px-6 py-3 text-left text-sm font-semibold text-gray-900" scope="col">Status</th>
                        <th class="bg-gray-50 px-6 py-3 text-left text-sm font-semibold text-gray-900" scope="col">Action</th>
                        <th class="bg-gray-50 px-6 py-3 text-left text-sm font-semibold text-gray-900" scope="col">Last Index</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                    <tr class="bg-white align-baseline">
                        <td class="w-full max-w-0 whitespace-nowrap px-6 py-4 text-sm text-gray-900 align-middle">
                            <div class="group flex items-center space-x-8 truncate text-sm">
                                <div class="flex space-x-2">
                                    <!-- Heroicon name: mini/banknotes -->
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-gray-500">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                                    </svg>

                                    <p class="truncate text-gray-500 group-hover:text-gray-900">Products</p>
                                </div>

                                <div class="w-full bg-gray-200 rounded-full dark:bg-gray-700 hidden">
                                    <div data-index="item" class="progress bg-blue-600 text-xs font-medium text-blue-100 text-center p-0.5 leading-none rounded-full" style="width: 45%"> 45%</div>
                                </div>
                            </div>

                        </td>
                        <td class="hidden whitespace-nowrap px-6 py-4 text-sm text-gray-500 md:block">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 capitalize">success</span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-500">
                            <form>
                                <button
                                        type="submit"
                                        class="disabled:opacity-75 disabled:cursor-not-allowed start-reindex inline-flex items-center rounded-full border border-transparent bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                        data-index="item"
                                >
                                    Reindex
                                </button>
                            </form>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-500">
                            <time datetime="2020-07-11">July 11, 2020</time>
                        </td>
                    </tr>

                    </tbody>
                </table>
            </div>
        </div>
</div>