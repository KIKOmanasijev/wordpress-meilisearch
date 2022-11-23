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

<div class="plugin-dashboard-wrapper">

    <!-- Compiling Tailwind classes so we can use them dynamically -->
    <template class="hidden bg-gray-600 bg-orange-600 bg-green-100 text-white text-orange-100 text-green-700 bg-green-500"></template>
    <div class="grid lg:grid-cols-4 gap-12 lg:gap-16">
        <div class="plugin-actions lg:col-span-3">
            <h2 class="mt-8 max-w-6xl text-xl mb-4 font-medium leading-6 text-gray-900">All Indexes</h2>
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
                                <?php foreach ( $cpts ?? [] as $cpt ){
                                    $icon = get_post_type_object( $cpt )->menu_icon;
                                ?>
                                    <tr class="bg-white align-baseline">
                                        <td class="w-full max-w-0 whitespace-nowrap px-6 py-4 text-sm text-gray-900 align-middle">
                                            <div class="group flex items-center space-x-8 truncate text-sm">
                                                <div class="flex space-x-2">
                                                    <div class="wp-menu-image dashicons-before <?= isset($icon) && strlen($icon) ? $icon : 'dashicons-admin-page' ?>"></div>

                                                    <p class="min-w-[120px] truncate text-gray-500 group-hover:text-gray-900 capitalize"><?= $cpt ?></p>
                                                </div>

                                                <div class="w-full bg-gray-200 rounded-full dark:bg-gray-700 hidden">
                                                    <div data-index="<?= esc_attr( $cpt ) ?>" class="progress bg-orange-600 text-xs font-medium text-orange-100 text-center p-0.5 leading-none rounded-full" style="width: 45%"> 45%</div>
                                                </div>
                                            </div>

                                        </td>
                                        <td class="hidden whitespace-nowrap px-6 py-4 text-sm text-gray-500 md:block">
                                            <span
                                                data-index="<?= esc_attr( $cpt ) ?>" class="status-badge inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-600 text-white capitalize ">
                                                idle
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-500">
                                            <button
                                                    class="disabled:opacity-75 disabled:cursor-not-allowed start-reindex inline-flex items-center rounded-full border border-transparent bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                                    data-index="<?= esc_attr( $cpt ) ?>"
                                            >
                                                Reindex
                                            </button>
                                            <button
                                                    class="disabled:opacity-75 disabled:cursor-not-allowed clear-index inline-flex items-center rounded-full border border-transparent bg-red-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                                    data-index="<?= esc_attr( $cpt ) ?>"
                                            >
                                                Clear
                                            </button>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-500">
                                            <time datetime="<?= get_option("meilisearch_${cpt}_last_index") ?: 'never' ?>"><?php echo get_option("meilisearch_${cpt}_last_index") ?: 'Never' ?></time>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
        </div>
        <div class="plugin-logs">
            <h2 class="mt-8 max-w-6xl text-lg font-medium leading-6 text-gray-900">Error Logs</h2>
            <!-- component -->
            <div id="error-logs-parent" class="sm:flex sm:space-x-4 mt-2 blur-lg cursor-not-allowed">
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow transform transition-all mb-4 w-full">
                    <div class="bg-white p-5">
                        <div class="sm:flex sm:items-start">
                            <div class="text-center sm:mt-0 sm:ml-2 sm:text-left">
                                <h3 class="text-sm leading-6 font-medium text-gray-400">Indexed 👍</h3>
                                <p id="countIndexed" class="text-3xl font-bold text-black">0</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow transform transition-all mb-4 w-full">
                    <div class="bg-white p-5">
                        <div class="sm:flex sm:items-start">
                            <div class="text-center sm:mt-0 sm:ml-2 sm:text-left">
                                <h3 class="text-sm leading-6 font-medium text-gray-400">Failed 🚨</h3>
                                <p id="countFailed" class="text-3xl font-bold text-black">0</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>